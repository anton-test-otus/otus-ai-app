import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'
import { flushPromises, mount } from '@vue/test-utils'
import LinkNoteModal from '@/components/LinkNoteModal.vue'
import { searchApi } from '@/api/search'
import { createListItem } from '@/stores/__tests__/fixtures/notes'

vi.mock('@/api/search', () => ({
  searchApi: {
    search: vi.fn(),
    searchByTitle: vi.fn(),
  },
}))

const primeStubs = {
  Dialog: {
    template: '<div><slot /></div>',
    props: ['visible'],
  },
  InputText: {
    props: ['modelValue'],
    emits: ['update:modelValue', 'input'],
    template:
      '<input :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value); $emit(\'input\', $event)" />',
  },
  ProgressSpinner: true,
}

describe('LinkNoteModal', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    vi.mocked(searchApi.searchByTitle).mockReset()
    vi.mocked(searchApi.search).mockReset()
    vi.mocked(searchApi.searchByTitle).mockResolvedValue({
      data: [
        createListItem({ id: 'note-1', title: 'Hello World' }),
        createListItem({ id: 'exclude-me', title: 'Current Note' }),
      ],
      meta: {
        currentPage: 1,
        perPage: 10,
        total: 2,
        totalPages: 1,
      },
    })
  })

  afterEach(() => {
    vi.useRealTimers()
  })

  it('calls searchByTitle instead of search when typing a query', async () => {
    const wrapper = mount(LinkNoteModal, {
      props: {
        visible: true,
      },
      global: {
        stubs: primeStubs,
      },
    })

    await wrapper.find('input').setValue('hello')
    await wrapper.find('input').trigger('input')
    vi.advanceTimersByTime(300)
    await flushPromises()

    expect(searchApi.searchByTitle).toHaveBeenCalledWith({
      q: 'hello',
      page: 1,
      perPage: 10,
    })
    expect(searchApi.search).not.toHaveBeenCalled()
  })

  it('filters out excludeNoteId from rendered search results', async () => {
    const wrapper = mount(LinkNoteModal, {
      props: {
        visible: true,
        excludeNoteId: 'exclude-me',
      },
      global: {
        stubs: primeStubs,
      },
    })

    await wrapper.find('input').setValue('hello')
    await wrapper.find('input').trigger('input')
    vi.advanceTimersByTime(300)
    await flushPromises()

    expect(searchApi.searchByTitle).toHaveBeenCalledWith({
      q: 'hello',
      page: 1,
      perPage: 10,
    })
    expect(wrapper.text()).toContain('Hello World')
    expect(wrapper.text()).not.toContain('Current Note')
  })
})
