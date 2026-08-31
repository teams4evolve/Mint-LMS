import { getAdminConfig, mintApi } from './api.js';

export { getAdminConfig, mintApi };

function debounce(fn, delay) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

function courseBuilder(courseId) {
  const config = getAdminConfig();
  let sectionSortable = null;
  const lessonSortables = new Map();

  return {
    courseId,
    course: {},
    sections: [],
    loading: true,
    error: '',
    saveStatus: '',
    publishing: false,
    selected: null,
    expanded: {},
    editingKey: '',
    editingValue: '',
    previewUrl: '',
    lessonMode: 'text',
    lessonQuiz: null,
    quizLoading: false,
    quizSaving: false,
    editorReady: false,

    buildPreviewUrl(courseId) {
      const base = config.urls.playerPage;
      if (!base) return '#';
      try {
        const url = new URL(base, window.location.origin);
        url.searchParams.set('mint_course', String(courseId));
        return url.toString();
      } catch {
        return `${base}${base.includes('?') ? '&' : '?'}mint_course=${courseId}`;
      }
    },

    get selectedSection() {
      if (!this.selected || this.selected.type !== 'section') return null;
      return this.sections.find((s) => s.id === this.selected.id) || null;
    },

    get selectedLesson() {
      if (!this.selected || this.selected.type !== 'lesson') return null;
      for (const section of this.sections) {
        const lesson = section.lessons.find((l) => l.id === this.selected.id);
        if (lesson) return lesson;
      }
      return null;
    },

  debouncedSaveSection: null,
  debouncedSaveLesson: null,

    async init() {
      this.debouncedSaveSection = debounce((section) => this.saveSection(section), 800);
      this.debouncedSaveLesson = debounce((lesson) => this.saveLesson(lesson), 800);
      await this.loadStructure();
    },

    async loadStructure() {
      this.loading = true;
      this.error = '';
      const root = document.getElementById('mint-course-builder');
      if (root) window.MintLMS.loading.show(root);

      try {
        const data = await mintApi(`courses/${this.courseId}/structure`);
        this.course = {
          title: data.title,
          slug: data.slug,
          status: data.status,
        };
        this.sections = (data.sections || []).map((section) => ({
          ...section,
          lessons: section.lessons || [],
        }));
        this.previewUrl = this.buildPreviewUrl(this.courseId);

        this.sections.forEach((section) => {
          this.expanded[section.id] = true;
        });

        this.$nextTick(() => {
          this.initSortables();
          this.initLessonEditor();
        });
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
      } finally {
        this.loading = false;
        if (root) window.MintLMS.loading.hide(root);
      }
    },

    initSortables() {
      const Sortable = window.MintLMS.Sortable;
      if (!Sortable) return;

      const sectionsEl = document.getElementById('mint-sections-sortable');
      if (sectionsEl) {
        if (sectionSortable) sectionSortable.destroy();
        sectionSortable = Sortable.create(sectionsEl, {
          handle: '.mint-handle-section',
          animation: 150,
          draggable: '[data-section-id]',
          onEnd: () => this.persistSectionOrder(),
        });
      }

      document.querySelectorAll('.mint-lessons-sortable').forEach((el) => {
        const sectionId = parseInt(el.dataset.sectionId, 10);
        if (lessonSortables.has(sectionId)) {
          lessonSortables.get(sectionId).destroy();
        }
        const instance = Sortable.create(el, {
          handle: '.mint-handle-lesson',
          animation: 150,
          draggable: '[data-lesson-id]',
          onEnd: () => this.persistLessonOrder(sectionId),
        });
        lessonSortables.set(sectionId, instance);
      });
    },

    statusLabel(status) {
      const labels = { draft: 'Draft', published: 'Published', archived: 'Archived' };
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

    isExpanded(sectionId) {
      return this.expanded[sectionId] !== false;
    },

    toggleSection(sectionId) {
      this.expanded[sectionId] = !this.isExpanded(sectionId);
    },

    isSelected(type, id) {
      return this.selected && this.selected.type === type && this.selected.id === id;
    },

    selectItem(type, id, sectionId = null) {
      if (this.selected?.type === 'lesson') {
        this.pullEditorContent();
      }
      this.selected = { type, id, sectionId };
      if (type === 'lesson') {
        this.syncLessonMode();
        this.$nextTick(() => this.syncEditorFromLesson());
        this.loadLessonQuiz(id);
      }
    },

    editorId() {
      return 'mint_lesson_content';
    },

    initLessonEditor() {
      if (this.editorReady) return;

      const bindEditor = () => {
        const editorId = this.editorId();
        const editor = window.tinymce?.get(editorId);
        if (editor && !editor._mintBound) {
          editor.on('change keyup', () => {
            if (!this.selectedLesson) return;
            this.selectedLesson.content = editor.getContent();
            this.debouncedSaveLesson(this.selectedLesson);
          });
          editor._mintBound = true;
        }

        const textarea = document.getElementById(editorId);
        if (textarea && !textarea.dataset.mintBound) {
          textarea.dataset.mintBound = '1';
          textarea.addEventListener('input', () => {
            if (!this.selectedLesson || window.tinymce?.get(editorId)) return;
            this.selectedLesson.content = textarea.value;
            this.debouncedSaveLesson(this.selectedLesson);
          });
        }
      };

      bindEditor();
      setTimeout(bindEditor, 600);
      setTimeout(bindEditor, 1500);
      this.editorReady = true;
    },

    pullEditorContent() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      const editorId = this.editorId();
      if (window.tinymce?.get(editorId)) {
        lesson.content = window.tinymce.get(editorId).getContent();
        return;
      }
      const textarea = document.getElementById(editorId);
      if (textarea) {
        lesson.content = textarea.value;
      }
    },

    syncEditorFromLesson() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      const editorId = this.editorId();
      const content = lesson.content || '';
      if (window.tinymce?.get(editorId)) {
        window.tinymce.get(editorId).setContent(content);
        return;
      }
      const textarea = document.getElementById(editorId);
      if (textarea) {
        textarea.value = content;
      }
    },

    syncLessonMode() {
      const lesson = this.selectedLesson;
      if (!lesson) return;
      this.lessonMode = lesson.videoUrl ? 'video' : 'text';
    },

    setLessonMode(mode) {
      this.lessonMode = mode;
    },

    totalLessonCount() {
      return this.sections.reduce((sum, section) => sum + section.lessons.length, 0);
    },

    saveStateLabel() {
      if (this.saveStatus === 'saving') return 'Saving…';
      if (this.saveStatus === 'saved') return 'Saved';
      return '';
    },

    breadcrumbSectionTitle() {
      if (!this.selected?.sectionId) return '';
      const section = this.sections.find((s) => s.id === this.selected.sectionId);
      return section?.title || '';
    },

    breadcrumbLessonLabel() {
      if (!this.selected || this.selected.type !== 'lesson') return '';
      let index = 0;
      for (const section of this.sections) {
        for (const lesson of section.lessons) {
          index += 1;
          if (lesson.id === this.selected.id) {
            return `Lesson ${index}`;
          }
        }
      }
      return 'Lesson';
    },

    addFirstLesson() {
      if (this.sections.length === 0) {
        this.addSection();
        return;
      }
      this.addLesson(this.sections[0].id);
    },

    async saveAndNext() {
      if (!this.selectedLesson || !this.selected) return;
      await this.saveLesson(this.selectedLesson);

      const sectionId = this.selected.sectionId;
      const section = this.sections.find((s) => s.id === sectionId);
      if (!section) return;

      const currentIndex = section.lessons.findIndex((l) => l.id === this.selected.id);
      if (currentIndex >= 0 && currentIndex < section.lessons.length - 1) {
        this.selectItem('lesson', section.lessons[currentIndex + 1].id, sectionId);
        return;
      }

      const sectionIndex = this.sections.findIndex((s) => s.id === sectionId);
      for (let i = sectionIndex + 1; i < this.sections.length; i += 1) {
        const nextSection = this.sections[i];
        if (nextSection.lessons.length > 0) {
          this.expanded[nextSection.id] = true;
          this.selectItem('lesson', nextSection.lessons[0].id, nextSection.id);
          return;
        }
      }
    },

    startEdit(type, id, value) {
      this.editingKey = `${type}-${id}`;
      this.editingValue = value;
      this.$nextTick(() => {
        const input = this.$refs.editInput;
        if (input) input.focus();
      });
    },

    cancelEdit() {
      this.editingKey = '';
      this.editingValue = '';
    },

    async commitEdit(type, id) {
      if (!this.editingKey) return;
      const value = this.editingValue.trim();
      this.cancelEdit();
      if (!value) return;

      if (type === 'section') {
        const section = this.sections.find((s) => s.id === id);
        if (section) {
          section.title = value;
          await this.saveSection(section);
        }
      } else if (type === 'lesson') {
        for (const section of this.sections) {
          const lesson = section.lessons.find((l) => l.id === id);
          if (lesson) {
            lesson.title = value;
            await this.saveLesson(lesson);
            break;
          }
        }
      }
    },

    setSaving() {
      this.saveStatus = 'saving';
    },

    setSaved() {
      this.saveStatus = 'saved';
      setTimeout(() => {
        if (this.saveStatus === 'saved') this.saveStatus = '';
      }, 2000);
    },

    async addSection() {
      try {
        const section = await mintApi(`courses/${this.courseId}/sections`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Section' }),
        });
        this.sections.push({ ...section, lessons: [] });
        this.expanded[section.id] = true;
        this.selectItem('section', section.id);
        this.$nextTick(() => this.initSortables());
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async addLesson(sectionId) {
      try {
        const lesson = await mintApi(`sections/${sectionId}/lessons`, {
          method: 'POST',
          body: JSON.stringify({ title: 'New Lesson', content: '', is_preview: false }),
        });
        const section = this.sections.find((s) => s.id === sectionId);
        if (section) {
          section.lessons.push(lesson);
          this.selectItem('lesson', lesson.id, sectionId);
          this.$nextTick(() => this.initSortables());
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async saveSection(section) {
      this.setSaving();
      try {
        await mintApi(`sections/${section.id}`, {
          method: 'PATCH',
          body: JSON.stringify({ title: section.title }),
        });
        this.setSaved();
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
      }
    },

    async saveLesson(lesson) {
      this.setSaving();
      if (this.selectedLesson?.id === lesson.id) {
        this.pullEditorContent();
      }
      try {
        await mintApi(`lessons/${lesson.id}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: lesson.title,
            content: lesson.content || '',
            video_url: lesson.videoUrl || '',
            attachment_id: lesson.attachmentId || null,
            is_preview: !!lesson.isPreview,
            available_after_days: lesson.availableAfterDays || null,
          }),
        });
        this.setSaved();
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteSection(sectionId) {
      if (!window.confirm('Delete this section and all its lessons?')) return;
      try {
        await mintApi(`sections/${sectionId}`, { method: 'DELETE' });
        this.sections = this.sections.filter((s) => s.id !== sectionId);
        if (this.selected?.type === 'section' && this.selected.id === sectionId) {
          this.selected = null;
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    async deleteLesson(lessonId, sectionId) {
      if (!window.confirm('Delete this lesson?')) return;
      try {
        await mintApi(`lessons/${lessonId}`, { method: 'DELETE' });
        const section = this.sections.find((s) => s.id === sectionId);
        if (section) {
          section.lessons = section.lessons.filter((l) => l.id !== lessonId);
        }
        if (this.selected?.type === 'lesson' && this.selected.id === lessonId) {
          this.selected = null;
        }
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    moveSection(index, direction) {
      const newIndex = index + direction;
      if (newIndex < 0 || newIndex >= this.sections.length) return;
      const items = [...this.sections];
      const [item] = items.splice(index, 1);
      items.splice(newIndex, 0, item);
      this.sections = items;
      this.persistSectionOrder();
    },

    moveLesson(sectionId, index, direction) {
      const section = this.sections.find((s) => s.id === sectionId);
      if (!section) return;
      const newIndex = index + direction;
      if (newIndex < 0 || newIndex >= section.lessons.length) return;
      const items = [...section.lessons];
      const [item] = items.splice(index, 1);
      items.splice(newIndex, 0, item);
      section.lessons = items;
      this.persistLessonOrder(sectionId);
    },

    async persistSectionOrder() {
      const ids = this.sections.map((s) => s.id);
      try {
        await mintApi(`courses/${this.courseId}/sections/reorder`, {
          method: 'POST',
          body: JSON.stringify({ ids }),
        });
      } catch (err) {
        window.MintLMS.toast.error(err.message);
        await this.loadStructure();
      }
    },

    async persistLessonOrder(sectionId) {
      const section = this.sections.find((s) => s.id === sectionId);
      if (!section) return;
      const ids = section.lessons.map((l) => l.id);
      try {
        await mintApi(`sections/${sectionId}/lessons/reorder`, {
          method: 'POST',
          body: JSON.stringify({ ids }),
        });
      } catch (err) {
        window.MintLMS.toast.error(err.message);
        await this.loadStructure();
      }
    },

    togglePreview() {
      if (!this.selectedLesson) return;
      this.selectedLesson.isPreview = !this.selectedLesson.isPreview;
      this.saveLesson(this.selectedLesson);
    },

    pickAttachment() {
      if (typeof wp === 'undefined' || !wp.media) {
        window.MintLMS.toast.error('Media library unavailable');
        return;
      }
      const frame = wp.media({
        title: 'Choose attachment',
        button: { text: 'Select' },
        multiple: false,
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        if (this.selectedLesson) {
          this.selectedLesson.attachmentId = attachment.id;
          this.saveLesson(this.selectedLesson);
        }
      });
      frame.open();
    },

    clearAttachment() {
      if (!this.selectedLesson) return;
      this.selectedLesson.attachmentId = null;
      this.saveLesson(this.selectedLesson);
    },

    async publishCourse() {
      this.publishing = true;
      try {
        const course = await mintApi(`courses/${this.courseId}/publish`, { method: 'POST' });
        this.course.status = course.status;
        window.MintLMS.toast.success('Course published');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.publishing = false;
      }
    },

    async loadLessonQuiz(lessonId) {
      this.quizLoading = true;
      this.lessonQuiz = null;
      try {
        const quiz = await mintApi(`lessons/${lessonId}/quiz`);
        this.lessonQuiz = quiz || this.emptyQuizState();
      } catch (err) {
        this.lessonQuiz = this.emptyQuizState();
      } finally {
        this.quizLoading = false;
      }
    },

    emptyQuizState() {
      return {
        id: null,
        title: 'Lesson Quiz',
        passPercent: 70,
        questions: [],
      };
    },

    async createQuiz() {
      if (!this.selectedLesson) return;
      this.quizSaving = true;
      try {
        const quiz = await mintApi(`lessons/${this.selectedLesson.id}/quiz`, {
          method: 'POST',
          body: JSON.stringify({
            title: this.lessonQuiz.title || 'Lesson Quiz',
            pass_percent: this.lessonQuiz.passPercent || 70,
          }),
        });
        this.lessonQuiz = quiz;
        window.MintLMS.toast.success('Quiz created');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async saveQuiz() {
      if (!this.lessonQuiz?.id) {
        await this.createQuiz();
        return;
      }
      this.quizSaving = true;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: this.lessonQuiz.title,
            pass_percent: this.lessonQuiz.passPercent,
          }),
        });
        this.lessonQuiz = quiz;
        window.MintLMS.toast.success('Quiz saved');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async deleteQuiz() {
      if (!this.lessonQuiz?.id || !window.confirm('Delete this quiz and all questions?')) return;
      try {
        await mintApi(`quizzes/${this.lessonQuiz.id}`, { method: 'DELETE' });
        this.lessonQuiz = this.emptyQuizState();
        window.MintLMS.toast.success('Quiz deleted');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },

    addQuizQuestion(type = 'mcq') {
      if (!this.lessonQuiz) this.lessonQuiz = this.emptyQuizState();
      const question = {
        _key: Date.now(),
        type,
        prompt: '',
        options: type === 'true_false' ? ['True', 'False'] : ['', ''],
        correctAnswer: type === 'true_false' ? 'true' : '',
        sortOrder: this.lessonQuiz.questions.length,
      };
      this.lessonQuiz.questions.push(question);
    },

    addMcqOption(question) {
      if (question.options.length >= 4) return;
      question.options.push('');
    },

    removeMcqOption(question, index) {
      if (question.options.length <= 2) return;
      const removed = question.options[index];
      question.options.splice(index, 1);
      if (question.correctAnswer === removed) {
        question.correctAnswer = '';
      }
    },

    async saveQuizQuestion(question) {
      if (!this.lessonQuiz?.id) {
        await this.createQuiz();
      }
      if (!this.lessonQuiz?.id) return;

      const payload = {
        type: question.type,
        prompt: question.prompt,
        options: question.type === 'true_false' ? ['True', 'False'] : question.options.filter((o) => o.trim()),
        correct_answer: question.correctAnswer,
      };

      this.quizSaving = true;
      try {
        let quiz;
        if (question.id) {
          quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${question.id}`, {
            method: 'PATCH',
            body: JSON.stringify(payload),
          });
        } else {
          quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions`, {
            method: 'POST',
            body: JSON.stringify(payload),
          });
        }
        this.lessonQuiz = quiz;
        window.MintLMS.toast.success('Question saved');
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      } finally {
        this.quizSaving = false;
      }
    },

    async deleteQuizQuestion(question) {
      if (!question.id || !this.lessonQuiz?.id) {
        this.lessonQuiz.questions = this.lessonQuiz.questions.filter((q) => q !== question);
        return;
      }
      if (!window.confirm('Delete this question?')) return;
      try {
        const quiz = await mintApi(`quizzes/${this.lessonQuiz.id}/questions/${question.id}`, {
          method: 'DELETE',
        });
        this.lessonQuiz = quiz;
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

function courseEdit(courseId) {
  return {
    courseId,
    loading: true,
    error: '',
    saving: false,
    saveStatus: '',
    featuredImageUrl: '',
    form: {
      title: '',
      description: '',
      featuredImageId: null,
      enrollmentType: 'open',
      status: 'draft',
    },

    async init() {
      await this.load();
    },

    async load() {
      this.loading = true;
      this.error = '';
      try {
        const course = await mintApi(`courses/${this.courseId}`);
        this.form = {
          title: course.title,
          description: course.description || '',
          featuredImageId: course.featuredImageId,
          enrollmentType: course.enrollmentType,
          status: course.status,
        };
        if (course.featuredImageId) {
          await this.loadFeaturedImage(course.featuredImageId);
        }
      } catch (err) {
        this.error = err.message;
        window.MintLMS.toast.error(err.message);
      } finally {
        this.loading = false;
      }
    },

    async loadFeaturedImage(id) {
      try {
        const response = await fetch(`/wp-json/wp/v2/media/${id}`, {
          headers: { 'X-WP-Nonce': getAdminConfig().nonce },
        });
        if (response.ok) {
          const media = await response.json();
          this.featuredImageUrl = media.source_url || '';
        }
      } catch {
        this.featuredImageUrl = '';
      }
    },

    pickImage() {
      if (typeof wp === 'undefined' || !wp.media) {
        window.MintLMS.toast.error('Media library unavailable');
        return;
      }
      const frame = wp.media({
        title: 'Choose featured image',
        button: { text: 'Select' },
        multiple: false,
        library: { type: 'image' },
      });
      frame.on('select', () => {
        const attachment = frame.state().get('selection').first().toJSON();
        this.form.featuredImageId = attachment.id;
        this.featuredImageUrl = attachment.url;
      });
      frame.open();
    },

    clearImage() {
      this.form.featuredImageId = null;
      this.featuredImageUrl = '';
    },

    async save() {
      this.saving = true;
      this.saveStatus = 'saving';
      try {
        await mintApi(`courses/${this.courseId}`, {
          method: 'PATCH',
          body: JSON.stringify({
            title: this.form.title,
            description: this.form.description,
            featured_image_id: this.form.featuredImageId,
            enrollment_type: this.form.enrollmentType,
            status: this.form.status,
          }),
        });
        this.saveStatus = 'saved';
        window.MintLMS.toast.success('Settings saved');
        setTimeout(() => {
          if (this.saveStatus === 'saved') this.saveStatus = '';
        }, 2000);
      } catch (err) {
        this.saveStatus = '';
        window.MintLMS.toast.error(err.message);
      } finally {
        this.saving = false;
      }
    },

    async deleteCourse() {
      if (!window.confirm('Permanently delete this course and all its content? This cannot be undone.')) {
        return;
      }
      try {
        await mintApi(`courses/${this.courseId}`, { method: 'DELETE' });
        window.location.href = getAdminConfig().urls.courses;
      } catch (err) {
        window.MintLMS.toast.error(err.message);
      }
    },
  };
}

export { courseBuilder, courseEdit, debounce };
