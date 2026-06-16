export function buildFilterCriteriaKey(folderId?: string | null, tagIds?: string[]): string {
  return JSON.stringify({
    folderId: folderId ?? null,
    tags: [...(tagIds ?? [])].sort(),
  })
}
