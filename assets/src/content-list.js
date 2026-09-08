import { mintApi, getAdminConfig } from './api.js';

/**
 * Shared Alpine data for A8 Lessons / A9 Quizzes admin lists.
 * @param {'lessons'|'quizzes'} type
 */
function contentList(type) {
  const config = getAdminConfig();
  const isQuizzes = type === 'quizzes';
  const apiBase = `content/${type}`;
  const noun = isQuizzes ? 'quizzes' : 'lessons';
  const nounSingular = isQuizzes ? 'quiz' : 'lesson';

  return {
    type,
    items: [],
    loading: true,
    error: '',
    search: '',
    statusFilter: 'all',
    page: 1,
    perPage: 20,
    total: 0,
    totalPages: 1,
    trashTotal: 0,
    hoverRow: null,

    init() {
      this.$nextTick(() => {
        const input = document.querySelector('#mint-lms-root .mint-search-input');
        if (!input) return;
        let timer = null;
        input.addEventListener('input', () => {
          clearTimeout(timer);
          timer = setTimeout(() => {
            this.search = input.value;
            this.loadItems(1);
          }, 400);
        });
      });
      this.loadItems(1);
    },

    filterTabs() {
      const tabs = [
        { value: 'all', label: 'All' },
        { value: 'published', label: 'Live' },
        { value: 'draft', label: 'Drafts' },
        { value: 'archived', label: 'Hidden' },
      ];
      if (this.trashTotal > 0) {
        tabs.push({ value: 'trashed', label: `Trash (${this.trashTotal})` });
      }
      return tabs;
    },

    summaryLine() {
      const n = this.total;
      if (isQuizzes) {
        return n === 1 ? '1 quiz' : `${n} quizzes`;
      }
      return n === 1 ? '1 lesson' : `${n} lessons`;
    },

    setFilter(status) {
      this.statusFilter = status;
      this.loadItems(1);
    },

    isEmpty() {
      return !this.loading && this.items.length === 0 && this.statusFilter !== 'trashed';
    },

    isEmptyTrash() {
      return !this.loading && this.items.length === 0 && this.statusFilter === 'trashed';
    },

    editUrl(item) {
      // Quizzes: always bounce through edit resolver so missing/trashed links are repaired.
      if (isQuizzes && item.id) {
        const editQuiz = config.urls.editQuiz || 'admin.php?page=mint-lms-edit-quiz';
        return `${editQuiz}&quiz_id=${item.id}`;
      }
      if (!item.courseId) {
        return config.urls.lessons;
      }
      return `${config.urls.builder}&course_id=${item.courseId}&lesson_id=${item.id || item.lessonId}&from=lessons`;
    },

    viewUrl(item) {
      return this.editUrl(item);
    },

    async loadItems(page = 1) {
      this.loading = true;
      this.error = '';
      this.page = page;
      try {
        const params = new URLSearchParams({
          page: String(page),
          per_page: String(this.perPage),
        });
        if (this.search.trim()) {
          params.set('search', this.search.trim());
        }
        if (this.statusFilter !== 'all') {
          params.set('status', this.statusFilter);
        }
        const data = await mintApi(`${apiBase}?${params.toString()}`);
        this.items = data.items || [];
        this.total = data.total || 0;
        this.totalPages = Math.max(1, Math.ceil(this.total / this.perPage));
        this.trashTotal = Number(data.trashTotal) || 0;
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
        this.items = [];
      } finally {
        this.loading = false;
      }
    },

    async trashItem(item) {
      try {
        await mintApi(`${apiBase}/${item.id}/trash`, { method: 'POST' });
        window.MintLMS.toast.success('Moved to trash');
        this.hoverRow = null;
        await this.loadItems(this.statusFilter === 'trashed' ? this.page : 1);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async restoreItem(item) {
      try {
        await mintApi(`${apiBase}/${item.id}/restore`, { method: 'POST' });
        window.MintLMS.toast.success(`${nounSingular.charAt(0).toUpperCase()}${nounSingular.slice(1)} restored`);
        this.hoverRow = null;
        await this.loadItems(this.page);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteItem(item) {
      if (!window.confirm(`Delete this ${nounSingular} permanently? This cannot be undone.`)) return;
      try {
        await mintApi(`${apiBase}/${item.id}`, { method: 'DELETE' });
        window.MintLMS.toast.success(`${nounSingular.charAt(0).toUpperCase()}${nounSingular.slice(1)} deleted`);
        this.hoverRow = null;
        await this.loadItems(this.page);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

function lessonsList() {
  return contentList('lessons');
}

function quizzesList() {
  return contentList('quizzes');
}

export { contentList, lessonsList, quizzesList };
