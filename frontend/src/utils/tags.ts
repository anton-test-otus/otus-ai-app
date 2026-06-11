import { tagsApi } from '@/api/tags'
import type { Tag } from '@/types'

export async function resolveTagNamesToIris(tagNames: string[]): Promise<string[]> {
  let allTags = await tagsApi.getAll()
  const iris: string[] = []

  for (const name of tagNames) {
    const trimmed = name.trim()
    if (!trimmed) {
      continue
    }

    let tag = allTags.find((t) => t.name.toLowerCase() === trimmed.toLowerCase())
    if (!tag) {
      tag = await tagsApi.create(trimmed)
      allTags = [...allTags, tag]
    }

    iris.push(`/api/tags/${tag.id}`)
  }

  return iris
}

export function findTagByName(tags: Tag[], name: string): Tag | undefined {
  return tags.find((t) => t.name.toLowerCase() === name.toLowerCase())
}
