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

  document.querySelectorAll('form[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      const message = form.getAttribute('data-confirm') || 'Continue with this action?';
      if (!window.confirm(message)) event.preventDefault();
    });
  });

  document.querySelectorAll('form[data-submit-feedback]:not([data-pos-form])').forEach((form) => {
    form.addEventListener('submit', () => {
      const button = form.querySelector('button[type="submit"]');
      if (button && !button.disabled) {
        button.dataset.originalText = button.textContent;
        button.textContent = button.dataset.loadingText || 'Saving…';
        button.disabled = true;
      }
    });
  });

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
      else items.set(id, { id, name: button.dataset.name || 'Food', price: Number(button.dataset.price), stock, unit: button.dataset.unit || 'servings', quantity: 1 });
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
        const details = document.createElement('div');
        const name = document.createElement('strong');
        name.textContent = item.name;
        const price = document.createElement('small');
        price.textContent = currency.format(item.price) + ' each';
        details.append(name, price);
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
        row.append(details, controls);
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
