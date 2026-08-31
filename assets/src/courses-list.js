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

    init() {
      const params = new URLSearchParams(window.location.search);
      const focus = params.get('focus');
      if (focus === 'search') {
        this.$nextTick(() => {
          const input = this.$root.querySelector('.mint-search-input');
          if (input) input.focus();
        });
      }
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

    statusLabel(status) {
      const labels = { draft: 'Draft', published: 'Live', archived: 'Hidden' };
      return labels[status] || status;
    },

    statusBadgeClass(status) {
      const map = {
        draft: 'mint-badge mint-badge--draft',
        published: 'mint-badge mint-badge--published',
        archived: 'mint-badge mint-badge--archived',
      };
      return map[status] || map.draft;
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

    async deleteCourse(courseId) {
      if (!window.confirm('Delete this course? This cannot be undone.')) return;
      try {
        await mintApi(`courses/${courseId}`, { method: 'DELETE' });
        window.MintLMS.toast.success('Course deleted');
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
