(function () {
  const cfg = window.mintLmsNativeEditor || {};
  const postId = parseInt(cfg.postId, 10) || 0;
  const nonce = cfg.discardNonce || '';
  const ajaxUrl = cfg.ajaxUrl || '';

  let preserve = false;

  function lockAutosave() {
    if (!window.wp || !wp.data || typeof wp.data.dispatch !== 'function') {
      return false;
    }
    const editor = wp.data.dispatch('core/editor');
    if (!editor || typeof editor.lockPostAutosaving !== 'function') {
      return false;
    }
    editor.lockPostAutosaving('mintlms');
    return true;
  }

  function armLock() {
    if (lockAutosave()) {
      return;
    }
    if (!window.wp || !wp.data || typeof wp.data.subscribe !== 'function') {
      return;
    }
    const unsub = wp.data.subscribe(function () {
      if (lockAutosave()) {
        unsub();
      }
    });
  }

  if (window.wp && typeof wp.domReady === 'function') {
    wp.domReady(armLock);
  } else {
    armLock();
  }

  function isPreserveUrl(href) {
    if (!href) {
      return false;
    }
    return (
      href.indexOf('mint-lms-builder') !== -1 ||
      href.indexOf('mint-lms-lesson-edit') !== -1 ||
      href.indexOf('mint-lms-edit-quiz') !== -1 ||
      href.indexOf('mint-lms-course-edit') !== -1
    );
  }

  document.addEventListener(
    'click',
    function (event) {
      const link = event.target && event.target.closest ? event.target.closest('a') : null;
      if (link && isPreserveUrl(link.href)) {
        preserve = true;
      }
    },
    true
  );

  document.addEventListener(
    'submit',
    function () {
      preserve = true;
    },
    true
  );

  function currentStatus() {
    try {
      const status = wp.data.select('core/editor').getCurrentPostAttribute('status');
      if (typeof status === 'string' && status !== '') {
        return status;
      }
    } catch (err) {
      // Editor store not ready; fall back to the PHP-provided status.
    }
    return cfg.status || '';
  }

  function discard() {
    if (preserve || postId <= 0 || !ajaxUrl || !nonce) {
      return;
    }
    if (currentStatus() !== 'auto-draft') {
      return;
    }
    const body = new FormData();
    body.append('action', 'mintlms_discard_auto_draft');
    body.append('nonce', nonce);
    body.append('post_id', String(postId));
    if (typeof navigator.sendBeacon === 'function') {
      navigator.sendBeacon(ajaxUrl, body);
      return;
    }
    window.fetch(ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin', keepalive: true });
  }

  window.addEventListener('pagehide', discard);
})();
