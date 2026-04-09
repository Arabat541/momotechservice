// Adds _id alias for backward compatibility with frontend code expecting MongoDB-style _id
export function withId<T extends { id: string }>(obj: T): T & { _id: string } {
  return { ...obj, _id: obj.id };
}

export function withIds<T extends { id: string }>(arr: T[]): (T & { _id: string })[] {
  return arr.map(withId);
}
