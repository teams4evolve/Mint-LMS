import { mintApi, getAdminConfig } from './api.js';

const TILE_HUES = [
  { bg: '#EFEBFF', ink: '#4B23C4' },
  { bg: '#E6F1FF', ink: '#1A5AA8' },
  { bg: '#E8F6EE', ink: '#0A6B4C' },
  { bg: '#FDEFE4', ink: '#9A4E12' },
  { bg: '#FBE9F2', ink: '#A22069' },
  { bg: '#FCF3DC', ink: '#7A5407' },
];

function coursesList() {
  const config = getAdminConfig();

  return {
    courses: [],
    loading: true,
    creating: false,
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
      const params = new URLSearchParams(window.location.search);
      const focus = params.get('focus');
      const urlSearch = params.get('search');
      if (urlSearch) {
        this.search = urlSearch;
      }

      this.$nextTick(() => {
        const input = document.querySelector('#mint-lms-root .mint-search-input');
        if (!input) return;
        if (this.search) {
          input.value = this.search;
        }
        if (focus === 'search') {
          input.focus();
        }
        let timer = null;
        input.addEventListener('input', () => {
          clearTimeout(timer);
          timer = setTimeout(() => {
            this.search = input.value;
            this.loadCourses(1);
          }, 400);
        });
      });

      this.loadCourses(1);
    },

    initDashboard() {
      this.loading = false;
    },

    tileColor(index) {
      return TILE_HUES[index % TILE_HUES.length];
    },

    builderUrl(courseId) {
      return `${config.urls.builder}&course_id=${courseId}`;
    },

    settingsUrl(courseId) {
      return `${config.urls.edit}&course_id=${courseId}`;
    },

    viewUrl(courseId) {
      const base = String(config.urls.playerPage || config.urls.catalogPage || '').replace(/\/+$/, '');
      if (!base) {
        return this.builderUrl(courseId);
      }
      const joiner = base.includes('?') ? '&' : '?';
      return `${base}${joiner}course_id=${courseId}`;
    },

    statusLabel(status) {
      const labels = { draft: 'Draft', published: 'Live', archived: 'Hidden', trashed: 'Trash' };
      return labels[status] || status;
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

    hueClass(index) {
      const hues = {
        1: 'mint-bg-hue-1 mint-text-hue-1i',
        2: 'mint-bg-hue-2 mint-text-hue-2i',
        3: 'mint-bg-hue-3 mint-text-hue-3i',
        4: 'mint-bg-hue-4 mint-text-hue-4i',
        5: 'mint-bg-hue-5 mint-text-hue-5i',
        6: 'mint-bg-hue-6 mint-text-hue-6i',
      };
      return hues[(index % 6) + 1];
    },

    statusBadgeClass(status) {
      const base = 'mint-inline-flex mint-items-center mint-whitespace-nowrap mint-rounded-md mint-px-[11px] mint-py-[5px] mint-text-[14px] mint-font-bold mint-leading-none';
      const map = {
        draft: `${base} mint-bg-[#ECEAF4] mint-text-[#33334A]`,
        published: `${base} mint-bg-[#E4F5EC] mint-text-[#0A6B4C]`,
        archived: `${base} mint-bg-[#F9EEDC] mint-text-[#7A4E08]`,
        trashed: `${base} mint-bg-[#F9E4E1] mint-text-[#B3261E]`,
      };
      return map[status] || map.draft;
    },

    studentsLabel(course) {
      const count = Number(course.studentCount);
      if (course.status === 'draft' && !(count > 0)) {
        return '—';
      }
      return String(Number.isFinite(count) ? count : 0);
    },

    finishedLabel(course) {
      if (course.completionRate === null || course.completionRate === undefined) {
        return '—';
      }
      return `${Math.round(Number(course.completionRate) || 0)}%`;
    },

    finishedWidth(course) {
      if (course.completionRate === null || course.completionRate === undefined) {
        return '0%';
      }
      return `${Math.min(100, Math.max(0, Number(course.completionRate) || 0))}%`;
    },

    finishedBar(course) {
      if (course.completionRate === null || course.completionRate === undefined) {
        return '#DCDAEA';
      }
      return '#98FBCB';
    },

    finishedInk(course) {
      if (course.completionRate === null || course.completionRate === undefined) {
        return '#5C5C77';
      }
      return '#0F0E1A';
    },

    courseMeta(course) {
      if (course.status === 'trashed') {
        return 'In trash';
      }
      const lessons = Number(course.lessonCount) || 0;
      const lessonLabel = lessons === 1 ? '1 lesson' : `${lessons} lessons`;
      if (course.status === 'published') {
        const when = this.formatShortDate(course.updatedAt);
        return when ? `${lessonLabel} · published ${when}` : lessonLabel;
      }
      if (course.status === 'archived') {
        return `${lessonLabel} · hidden from students`;
      }
      return `${lessonLabel} · not published`;
    },

    summaryLine() {
      const live = this.courses.filter((c) => c.status === 'published').length;
      const students = this.courses.reduce((sum, c) => sum + (Number(c.studentCount) || 0), 0);
      return `${this.total} courses · ${live} live · ${students} students enrolled`;
    },

    formatShortDate(iso) {
      if (!iso) return '';
      try {
        return new Date(iso).toLocaleDateString(undefined, { day: 'numeric', month: 'short' });
      } catch {
        return '';
      }
    },

    formatRelative(iso) {
      if (!iso) return '—';
      try {
        const date = new Date(iso);
        if (Number.isNaN(date.getTime())) return '—';

        const datePart = new Intl.DateTimeFormat(undefined, {
          year: 'numeric',
          month: '2-digit',
          day: '2-digit',
        }).format(date);
        const timePart = new Intl.DateTimeFormat(undefined, {
          hour: 'numeric',
          minute: '2-digit',
        }).format(date);

        return `${datePart} at ${timePart.toLowerCase()}`;
      } catch {
        return this.formatDate(iso);
      }
    },

    formatDate(iso) {
      if (!iso) return '';
      try {
        return new Date(iso).toLocaleDateString(undefined, {
          year: 'numeric',
          month: 'short',
          day: 'numeric',
        });
      } catch {
        return iso;
      }
    },

    setFilter(status) {
      this.statusFilter = status;
      this.loadCourses(1);
    },

    isEmptyCourses() {
      return !this.loading && this.courses.length === 0 && this.statusFilter !== 'trashed';
    },

    isEmptyTrash() {
      return !this.loading && this.courses.length === 0 && this.statusFilter === 'trashed';
    },

    async loadCourses(page = 1) {
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
        const data = await mintApi(`courses?${params.toString()}`);
        this.courses = data.items || [];
        this.total = data.total || 0;
        this.totalPages = Math.max(1, Math.ceil(this.total / this.perPage));
        this.trashTotal = Number(data.trashTotal) || 0;
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
        this.courses = [];
      } finally {
        this.loading = false;
      }
    },

    async createCourse() {
      this.creating = true;
      try {
        const course = await mintApi('courses', {
          method: 'POST',
          body: JSON.stringify({
            title: 'Untitled Course',
            enrollment_type: window.mintLmsAdmin?.defaultEnrollment || 'open',
          }),
        });
        window.location.href = this.builderUrl(course.id);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.creating = false;
      }
    },

    async trashCourse(courseId) {
      try {
        await mintApi(`courses/${courseId}/trash`, { method: 'POST' });
        window.MintLMS.toast.success('Moved to trash');
        this.hoverRow = null;
        if (this.statusFilter === 'trashed') {
          await this.loadCourses(this.page);
        } else {
          await this.loadCourses(1);
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async restoreCourse(courseId) {
      try {
        await mintApi(`courses/${courseId}/restore`, { method: 'POST' });
        window.MintLMS.toast.success('Course restored');
        this.hoverRow = null;
        await this.loadCourses(this.page);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteCourse(courseId) {
      if (!window.confirm('Delete this course permanently? This cannot be undone.')) return;
      try {
        await mintApi(`courses/${courseId}`, { method: 'DELETE' });
        window.MintLMS.toast.success('Course deleted');
        this.hoverRow = null;
        await this.loadCourses(this.page);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async duplicateCourse(courseId) {
      try {
        const [course, structure] = await Promise.all([
          mintApi(`courses/${courseId}`),
          mintApi(`courses/${courseId}/structure`),
        ]);

        const newCourse = await mintApi('courses', {
          method: 'POST',
          body: JSON.stringify({
            title: `Copy of ${course.title}`,
            description: course.description,
            featured_image_id: course.featuredImageId,
            enrollment_type: course.enrollmentType,
          }),
        });

        for (const section of structure.sections || []) {
          const newSection = await mintApi(`courses/${newCourse.id}/sections`, {
            method: 'POST',
            body: JSON.stringify({ title: section.title }),
          });

          for (const lesson of section.lessons || []) {
            await mintApi(`sections/${newSection.id}/lessons`, {
              method: 'POST',
              body: JSON.stringify({
                title: lesson.title,
                content: lesson.content || '',
                video_url: lesson.videoUrl || '',
                attachment_id: lesson.attachmentId || null,
                is_preview: !!lesson.isPreview,
              }),
            });
          }
        }

        window.MintLMS.toast.success('Course duplicated');
        window.location.href = this.builderUrl(newCourse.id);
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

export { coursesList };
