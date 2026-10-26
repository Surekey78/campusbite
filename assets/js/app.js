(() => {
  'use strict';

  const menuToggle = document.querySelector('[data-menu-toggle]');
  const nav = document.querySelector('[data-main-nav]');
  if (menuToggle && nav) {
    menuToggle.addEventListener('click', () => {
      const expanded = menuToggle.getAttribute('aria-expanded') === 'true';
      menuToggle.setAttribute('aria-expanded', expanded ? 'false' : 'true');
      nav.classList.toggle('is-open', !expanded);
    });
  }

  const profilePictureInput = document.querySelector('[data-profile-picture-input]');
  const removeProfilePicture = document.querySelector('[data-remove-profile-picture]');
  profilePictureInput?.addEventListener('change', () => {
    if (profilePictureInput.files?.length && removeProfilePicture) removeProfilePicture.checked = false;
  });
  removeProfilePicture?.addEventListener('change', () => {
    if (removeProfilePicture.checked && profilePictureInput) profilePictureInput.value = '';
  });

  const workspace = document.querySelector('[data-workspace-layout]');
  if (workspace) {
    const sidebarToggle = workspace.querySelector('[data-sidebar-toggle]');
    const sidebar = workspace.querySelector('#workspace-sidebar');
    const backdrop = workspace.querySelector('[data-sidebar-close]');
    const mobileViewport = window.matchMedia('(max-width: 1100px)');

    const setSidebarOpen = (open, returnFocus = false) => {
      workspace.classList.toggle('is-sidebar-open', open);
      document.body.classList.toggle('workspace-sidebar-open', open && mobileViewport.matches);
      if (sidebarToggle) {
        sidebarToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        sidebarToggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
      }
      if (backdrop) backdrop.hidden = !open;
      if (sidebar) {
        const hiddenOnMobile = mobileViewport.matches && !open;
        sidebar.inert = hiddenOnMobile;
        sidebar.setAttribute('aria-hidden', hiddenOnMobile ? 'true' : 'false');
      }
      if (open && sidebar) {
        const firstLink = sidebar.querySelector('a, button');
        firstLink?.focus({ preventScroll: true });
      } else if (returnFocus) {
        sidebarToggle?.focus({ preventScroll: true });
      }
    };

    sidebarToggle?.addEventListener('click', () => {
      const isOpen = workspace.classList.contains('is-sidebar-open');
      setSidebarOpen(!isOpen, isOpen);
    });
    backdrop?.addEventListener('click', () => setSidebarOpen(false, true));
    sidebar?.querySelectorAll('a').forEach((link) => {
      link.addEventListener('click', () => {
        if (mobileViewport.matches) setSidebarOpen(false);
      });
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && workspace.classList.contains('is-sidebar-open')) {
        setSidebarOpen(false, true);
      }
    });
    mobileViewport.addEventListener('change', (event) => {
      setSidebarOpen(false);
    });
    setSidebarOpen(false);
  }

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm') || 'Continue with this action?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  document.querySelectorAll('form[data-submit-feedback]:not([data-pos-form])').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (form.checkValidity && !form.checkValidity()) {
        return;
      }
      const button = form.querySelector('button[type="submit"]');
      if (button && !button.disabled) {
        button.dataset.originalText = button.textContent;
        button.textContent = button.dataset.loadingText || 'Saving…';
        button.disabled = true;
      }
    });
  });

  document.addEventListener('invalid', (event) => {
    const target = event.target;
    if (target && target.form) {
      target.classList.add('is-invalid');
      target.setAttribute('aria-invalid', 'true');
      const button = target.form.querySelector('button[type="submit"]');
      if (button && button.dataset.originalText) {
        button.disabled = false;
        button.textContent = button.dataset.originalText;
      }
    }
  }, true);

  document.addEventListener('input', (event) => {
    const target = event.target;
    if (target && target.classList && target.classList.contains('is-invalid')) {
      target.classList.remove('is-invalid');
      target.removeAttribute('aria-invalid');
      const err = target.closest('.form-field')?.querySelector('.field-error-msg');
      if (err) err.remove();
    }
  });

  const highlightAndFocusError = () => {
    let firstInvalid = document.querySelector('.is-invalid, [aria-invalid="true"]');
    const errorToast = document.querySelector('[data-system-toast][data-toast-type="error"]');
    if (!firstInvalid && errorToast) {
      const text = (errorToast.textContent || '').toLowerCase();
      const inputs = Array.from(document.querySelectorAll('input:not([type="hidden"]), select, textarea'));
      for (const input of inputs) {
        const name = (input.name || '').toLowerCase();
        const id = (input.id || '').toLowerCase();
        const labelText = input.labels?.[0]?.textContent?.toLowerCase() || '';
        if (
          (name && text.includes(name.replace('_', ' '))) ||
          (id && text.includes(id.replace('-', ' '))) ||
          (labelText && text.includes(labelText.replace('*', '').trim()))
        ) {
          input.classList.add('is-invalid');
          input.setAttribute('aria-invalid', 'true');
          firstInvalid = input;
          break;
        }
      }
      if (!firstInvalid && inputs.length > 0) {
        const firstEditable = inputs.find((el) => !el.disabled && el.type !== 'submit');
        if (firstEditable) {
          firstEditable.classList.add('is-invalid');
          firstEditable.setAttribute('aria-invalid', 'true');
          firstInvalid = firstEditable;
        }
      }
    }
    if (firstInvalid) {
      firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
      try { firstInvalid.focus({ preventScroll: true }); } catch (e) {}
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', highlightAndFocusError);
  } else {
    highlightAndFocusError();
  }

  document.querySelectorAll('[data-system-toast]').forEach((toast) => {
    const dismissButton = toast.querySelector('[data-toast-dismiss]');
    let timer = null;
    const dismissToast = () => {
      if (timer !== null) window.clearTimeout(timer);
      toast.remove();
    };
    dismissButton?.addEventListener('click', dismissToast);
    if (toast.dataset.toastType === 'error') return;
    timer = window.setTimeout(dismissToast, toast.dataset.toastType === 'info' ? 8500 : 7000);
    const keepVisible = () => {
      if (timer !== null) window.clearTimeout(timer);
      timer = null;
    };
    toast.addEventListener('mouseenter', keepVisible, { once: true });
    toast.addEventListener('focusin', keepVisible, { once: true });
  });

  const liveNotificationRoot = document.querySelector('[data-live-notifications]');
  if (liveNotificationRoot && typeof window.fetch === 'function') {
    const pollUrl = liveNotificationRoot.dataset.pollUrl || '';
    const toastStack = liveNotificationRoot.querySelector('[data-live-notification-toasts]');
    const validBadgeClasses = new Set(['workspace-nav-badge', 'workspace-topbar-badge', 'student-bottom-nav-badge']);
    let cursor = Number.parseInt(liveNotificationRoot.dataset.afterId || '0', 10);
    if (!Number.isFinite(cursor) || cursor < 0) cursor = 0;
    let pollTimer = null;
    let requestInFlight = false;
    let pollingStopped = false;

    const setUnreadCount = (rawCount) => {
      const count = Math.max(0, Math.trunc(Number(rawCount) || 0));
      document.querySelectorAll('[data-live-unread-link]').forEach((link) => {
        const label = link.dataset.liveLabel || 'Notifications';
        const badgeClass = link.dataset.liveBadgeClass || '';
        let badge = link.querySelector('[data-live-unread-badge]');
        if (count > 0 && !badge && validBadgeClasses.has(badgeClass)) {
          badge = document.createElement('span');
          badge.className = badgeClass;
          badge.dataset.liveUnreadBadge = '';
          badge.setAttribute('aria-hidden', 'true');
          link.appendChild(badge);
        }
        if (count > 0 && badge) {
          badge.textContent = count > 99 ? '99+' : String(count);
          link.setAttribute('aria-label', label + ', ' + count + (count === 1 ? ' unread notification' : ' unread notifications'));
        } else {
          badge?.remove();
          link.setAttribute('aria-label', label);
        }
      });
    };

    const showLiveToast = (item) => {
      if (!toastStack || !item || typeof item.title !== 'string' || typeof item.body !== 'string') return;
      let target;
      try {
        target = new URL(String(item.open_url || ''), window.location.href);
      } catch (_error) {
        return;
      }
      if (target.origin !== window.location.origin) return;

      const toast = document.createElement('article');
      toast.className = 'live-notification-toast';
      toast.setAttribute('role', 'status');
      toast.setAttribute('aria-live', 'polite');
      const link = document.createElement('a');
      link.className = 'live-notification-link';
      link.href = target.href;
      const kicker = document.createElement('span');
      kicker.className = 'live-notification-kicker';
      kicker.textContent = 'New CampusBite update';
      const title = document.createElement('strong');
      title.textContent = item.title;
      const body = document.createElement('span');
      body.className = 'live-notification-body';
      body.textContent = item.body;
      link.append(kicker, title, body);
      const dismiss = document.createElement('button');
      dismiss.className = 'live-notification-dismiss';
      dismiss.type = 'button';
      dismiss.setAttribute('aria-label', 'Dismiss update');
      dismiss.textContent = '×';
      dismiss.addEventListener('click', () => toast.remove());
      toast.append(link, dismiss);
      toastStack.prepend(toast);
      while (toastStack.children.length > 3) toastStack.lastElementChild?.remove();
      const timer = window.setTimeout(() => toast.remove(), 12000);
      toast.addEventListener('mouseenter', () => window.clearTimeout(timer), { once: true });
      toast.addEventListener('focusin', () => window.clearTimeout(timer), { once: true });
    };

    const schedulePoll = (delay) => {
      if (pollTimer !== null) window.clearTimeout(pollTimer);
      if (!pollingStopped) pollTimer = window.setTimeout(pollNotifications, delay);
    };

    const pollNotifications = async () => {
      pollTimer = null;
      if (pollingStopped || requestInFlight) return;
      if (document.visibilityState !== 'visible') {
        schedulePoll(30000);
        return;
      }
      requestInFlight = true;
      try {
        const url = new URL(pollUrl, window.location.href);
        url.searchParams.set('after', String(cursor));
        const response = await window.fetch(url.href, {
          method: 'GET',
          credentials: 'same-origin',
          cache: 'no-store',
          headers: { Accept: 'application/json' },
        });
        if (response.status === 401) {
          pollingStopped = true;
          return;
        }
        if (!response.ok) throw new Error('Notification check failed.');
        const payload = await response.json();
        if (!payload || payload.ok !== true) throw new Error('Invalid notification response.');
        setUnreadCount(payload.unread_count);
        if (Array.isArray(payload.notifications)) {
          payload.notifications.forEach((item) => {
            const id = Number(item.id);
            if (!Number.isSafeInteger(id) || id <= cursor) return;
            cursor = id;
            liveNotificationRoot.dataset.afterId = String(cursor);
            showLiveToast(item);
          });
        }
      } catch (_error) {
        // Keep the page usable offline; the next poll retries without interrupting the user.
      } finally {
        requestInFlight = false;
        if (!pollingStopped) schedulePoll(document.visibilityState === 'visible' ? 12000 : 30000);
      }
    };

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'visible') schedulePoll(0);
    });
    schedulePoll(0);
  }

  const pos = document.querySelector('[data-pos-app]');
  if (pos) {
    const form = pos.querySelector('[data-pos-form]');
    const list = pos.querySelector('[data-pos-list]');
    const cart = pos.querySelector('[data-pos-cart]');
    const totalNode = pos.querySelector('[data-pos-total]');
    const cashNode = pos.querySelector('[data-pos-cash]');
    const changeNode = pos.querySelector('[data-pos-change]');
    const feedback = pos.querySelector('[data-pos-feedback]');
    const search = pos.querySelector('[data-pos-search]');
    const category = pos.querySelector('[data-pos-category]');
    const complete = pos.querySelector('[data-pos-complete]');
    const items = new Map();
    const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });

    const message = (text, isError = false) => {
      if (!feedback) return;
      feedback.textContent = text;
      feedback.classList.toggle('alert-error', isError);
      feedback.classList.toggle('alert-info', !isError);
      feedback.hidden = text === '';
    };
    const updateCash = () => {
      const totalCents = Array.from(items.values()).reduce((sum, item) => sum + Math.round(item.price * 100) * item.quantity, 0);
      if (totalNode) totalNode.textContent = currency.format(totalCents / 100);
      const enteredCash = Number(cashNode?.value || 0);
      const cashCents = Number.isFinite(enteredCash) ? Math.max(0, Math.round(enteredCash * 100)) : 0;
      if (changeNode) changeNode.textContent = currency.format(Math.max(0, cashCents - totalCents) / 100);
      if (complete) complete.disabled = items.size === 0 || cashCents < totalCents;
    };
    const add = (button) => {
      const id = Number(button.dataset.id);
      const stock = Number(button.dataset.stock);
      const current = items.get(id);
      if ((current?.quantity || 0) >= stock) {
        message('There is no more stock available for this food.', true);
        return;
      }
      if (current) current.quantity += 1;
      else items.set(id, { id, name: button.dataset.name || 'Food', price: Number(button.dataset.price), stock, unit: button.dataset.unit || 'servings', image: button.dataset.image || '', quantity: 1 });
      message((button.dataset.name || 'Food') + ' added to the sale.');
      draw();
    };
    const changeQuantity = (id, amount) => {
      const item = items.get(id);
      if (!item) return;
      const next = item.quantity + amount;
      if (next > item.stock) { message('Stock limit reached for ' + item.name + '.', true); return; }
      if (next < 1) items.delete(id);
      else item.quantity = next;
      draw();
    };
    const draw = () => {
      if (!cart) return;
      cart.replaceChildren();
      if (items.size === 0) {
        const empty = document.createElement('p');
        empty.className = 'muted small';
        empty.textContent = 'Select a food to start this sale.';
        cart.appendChild(empty);
      }
      items.forEach((item) => {
        const row = document.createElement('div');
        row.className = 'pos-cart-row';
        const product = document.createElement('div');
        product.className = 'pos-cart-product';
        const thumbnail = document.createElement('span');
        thumbnail.className = 'pos-cart-image';
        thumbnail.setAttribute('aria-hidden', 'true');
        if (item.image) {
          const photo = document.createElement('img');
          photo.alt = '';
          photo.loading = 'eager';
          photo.decoding = 'async';
          photo.addEventListener('error', () => {
            photo.remove();
            thumbnail.textContent = '🍽';
          }, { once: true });
          photo.src = item.image;
          thumbnail.appendChild(photo);
        } else {
          thumbnail.textContent = '🍽';
        }
        const details = document.createElement('div');
        details.className = 'pos-cart-details';
        const name = document.createElement('strong');
        name.textContent = item.name;
        const price = document.createElement('small');
        price.textContent = currency.format(item.price) + ' each';
        details.append(name, price);
        product.append(thumbnail, details);
        const controls = document.createElement('div');
        controls.className = 'pos-qty';
        const minus = document.createElement('button');
        minus.type = 'button'; minus.className = 'button button-secondary button-small'; minus.setAttribute('aria-label', 'Remove one ' + item.name); minus.textContent = '−';
        minus.addEventListener('click', () => changeQuantity(item.id, -1));
        const count = document.createElement('span'); count.textContent = String(item.quantity); count.setAttribute('aria-live', 'polite');
        const plus = document.createElement('button');
        plus.type = 'button'; plus.className = 'button button-secondary button-small'; plus.setAttribute('aria-label', 'Add one ' + item.name); plus.textContent = '+';
        plus.addEventListener('click', () => changeQuantity(item.id, 1));
        controls.append(minus, count, plus);
        row.append(product, controls);
        cart.appendChild(row);
      });
      form.querySelectorAll('input[data-pos-hidden]').forEach((el) => el.remove());
      items.forEach((item) => {
        const hidden = document.createElement('input');
        hidden.type = 'hidden'; hidden.name = 'items[' + item.id + ']'; hidden.value = String(item.quantity); hidden.dataset.posHidden = '1';
        form.appendChild(hidden);
      });
      updateCash();
    };

    list?.querySelectorAll('[data-pos-add]').forEach((button) => button.addEventListener('click', () => add(button)));
    search?.addEventListener('input', () => {
      const q = search.value.trim().toLocaleLowerCase();
      list?.querySelectorAll('[data-pos-food]').forEach((card) => {
        const textMatch = card.dataset.search.includes(q);
        const categoryMatch = !category?.value || card.dataset.category === category.value;
        card.hidden = !(textMatch && categoryMatch);
      });
    });
    category?.addEventListener('change', () => search?.dispatchEvent(new Event('input')));
    cashNode?.addEventListener('input', updateCash);
    form?.addEventListener('submit', (event) => {
      const totalCents = Array.from(items.values()).reduce((sum, item) => sum + Math.round(item.price * 100) * item.quantity, 0);
      const enteredCash = Number(cashNode?.value || 0);
      const cashCents = Number.isFinite(enteredCash) ? Math.max(0, Math.round(enteredCash * 100)) : 0;
      if (items.size === 0 || cashCents < totalCents) {
        event.preventDefault();
        message(items.size === 0 ? 'Add at least one food before completing the sale.' : 'Cash received must cover the total.', true);
        return;
      }
      if (complete) { complete.disabled = true; complete.textContent = 'Recording sale…'; }
    });
    draw();
  }

  document.querySelectorAll('[data-print-receipt]').forEach((button) => {
    button.addEventListener('click', () => window.print());
  });
})();

  // Immediate navigation response and loading visibility
  document.querySelectorAll('.workspace-navigation a, .student-bottom-nav a, .main-nav a').forEach((link) => {
    link.addEventListener('click', (event) => {
      const href = link.getAttribute('href');
      if (href && !href.startsWith('#') && !href.startsWith('javascript:') && !event.ctrlKey && !event.metaKey && link.target !== '_blank') {
        const main = document.querySelector('.workspace-site-main, .site-main');
        if (main) main.classList.add('is-loading');
      }
    });
  });
