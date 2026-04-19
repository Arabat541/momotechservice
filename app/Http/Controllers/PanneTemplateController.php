<?php

namespace App\Http\Controllers;

use App\Models\DeviceModel;
use App\Models\PanneTemplate;
use Illuminate\Http\Request;

class PanneTemplateController extends Controller
{
    public function index(Request $request)
    {
        $shopId  = $request->attributes->get('shopId');
        $models  = DeviceModel::with('panneTemplates')->where('shopId', $shopId)->orderBy('marque')->orderBy('modele')->get();

        return view('panne-templates.index', compact('models'));
    }

    public function storeModel(Request $request)
    {
        $shopId = $request->attributes->get('shopId');

        $validated = $request->validate([
            'marque'  => 'required|string|max:100',
            'modele'  => 'required|string|max:100',
            'type'    => 'required|in:smartphone,tablette,ordinateur,autre',
        ]);

        $validated['shopId'] = $shopId;
        DeviceModel::create($validated);

        return back()->with('success', "Modèle {$validated['marque']} {$validated['modele']} créé.");
    }

    public function destroyModel(Request $request, int $id)
    {
        $shopId = $request->attributes->get('shopId');
        DeviceModel::where('id', $id)->where('shopId', $shopId)->firstOrFail()->delete();

        return back()->with('success', 'Modèle supprimé.');
    }

    public function storeTemplate(Request $request, int $modelId)
    {
        $shopId = $request->attributes->get('shopId');
        DeviceModel::where('id', $modelId)->where('shopId', $shopId)->firstOrFail();

        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'prix_estime' => 'nullable|numeric|min:0|max:99999999',
        ]);

        $validated['device_model_id'] = $modelId;
        PanneTemplate::create($validated);

        return back()->with('success', 'Panne ajoutée.');
    }

    public function destroyTemplate(Request $request, int $templateId)
    {
        $shopId   = $request->attributes->get('shopId');
        $template = PanneTemplate::findOrFail($templateId);
        DeviceModel::where('id', $template->device_model_id)->where('shopId', $shopId)->firstOrFail();

        $template->delete();

        return back()->with('success', 'Panne supprimée.');
    }

    /** API endpoint: return templates for a given device model (used in repair form) */
    public function apiTemplates(Request $request, int $modelId)
    {
        $shopId    = $request->attributes->get('shopId');
        $model     = DeviceModel::with('panneTemplates')->where('id', $modelId)->where('shopId', $shopId)->firstOrFail();

        return response()->json($model->panneTemplates->map(fn ($t) => [
            'id'          => $t->id,
            'description' => $t->description,
            'prix_estime' => $t->prix_estime,
        ]));
    }
}
