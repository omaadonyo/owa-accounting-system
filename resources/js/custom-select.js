class CustomSelect {
    constructor(el) {
        this.wrapper = el;
        this.isOpen = false;
        if (!this.wrapper.querySelector('select')) return;
        this._init();
    }

    _init() {
        this._syncDisplay();

        this._onWrapperClick = (e) => {
            const trigger = e.target.closest('[data-cs-trigger]');
            const option = e.target.closest('[data-cs-option]');
            const search = e.target.closest('[data-cs-search]');
            if (trigger) { e.stopPropagation(); this._toggle(); return; }
            if (option) { e.stopPropagation(); this._select(option.dataset.csValue, option.dataset.csLabel); return; }
            if (search) { e.stopPropagation(); return; }
        };

        this._onDocClick = (e) => {
            if (!this.wrapper.contains(e.target)) this._close();
        };

        this._onKeydown = (e) => {
            if (e.key === 'Escape' || e.key === 'Tab') this._close();
        };

        this._onWrapperChange = (e) => {
            if (e.target.matches('select')) this._syncDisplay();
        };

        this.wrapper.addEventListener('click', this._onWrapperClick);
        document.addEventListener('click', this._onDocClick);
        document.addEventListener('keydown', this._onKeydown);
        this.wrapper.addEventListener('change', this._onWrapperChange);

        const search = this.wrapper.querySelector('[data-cs-search]');
        if (search) {
            search.addEventListener('input', () => this._filter());
            search.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const visible = this.wrapper.querySelectorAll('[data-cs-option]:not(.hidden)');
                    if (visible.length === 1) {
                        this._select(visible[0].dataset.csValue, visible[0].dataset.csLabel);
                    }
                }
            });
        }
    }

    _toggle() { this.isOpen ? this._close() : this._open(); }

    _open() {
        const dd = this.wrapper.querySelector('[data-cs-dropdown]');
        if (!dd) return;
        this.isOpen = true;
        dd.classList.remove('hidden');
        this.wrapper.classList.add('cs-open');
        const search = this.wrapper.querySelector('[data-cs-search]');
        if (search) setTimeout(() => search.focus(), 50);
    }

    _close() {
        const dd = this.wrapper.querySelector('[data-cs-dropdown]');
        if (!dd) return;
        this.isOpen = false;
        dd.classList.add('hidden');
        this.wrapper.classList.remove('cs-open');
        const search = this.wrapper.querySelector('[data-cs-search]');
        if (search) { search.value = ''; this._filter(); }
    }

    _filter() {
        const q = (this.wrapper.querySelector('[data-cs-search]')?.value || '').toLowerCase();
        this.wrapper.querySelectorAll('[data-cs-option]').forEach(opt => {
            const text = (opt.textContent || '').toLowerCase();
            const label = (opt.dataset.csLabel || '').toLowerCase();
            opt.classList.toggle('hidden', !text.includes(q) && !label.includes(q));
        });
    }

    _select(val, label) {
        const select = this.wrapper.querySelector('select');
        if (!select) return;
        select.value = val;
        select.dispatchEvent(new Event('change', { bubbles: true }));
        select.dispatchEvent(new Event('input', { bubbles: true }));
        const display = this.wrapper.querySelector('[data-cs-display]');
        if (display) display.textContent = label;
        this.wrapper.querySelectorAll('[data-cs-option]').forEach(opt => {
            opt.classList.toggle('cs-selected', opt.dataset.csValue === val);
        });
        this._close();
    }

    _syncDisplay() {
        const select = this.wrapper.querySelector('select');
        const display = this.wrapper.querySelector('[data-cs-display]');
        if (!display || !select) return;
        const selected = select.options[select.selectedIndex];
        if (selected) {
            display.textContent = selected.text;
            this.wrapper.querySelectorAll('[data-cs-option]').forEach(opt => {
                opt.classList.toggle('cs-selected', opt.dataset.csValue === selected.value);
            });
        }
    }

    destroy() {
        this.wrapper.removeEventListener('click', this._onWrapperClick);
        document.removeEventListener('click', this._onDocClick);
        document.removeEventListener('keydown', this._onKeydown);
        this.wrapper.removeEventListener('change', this._onWrapperChange);
    }
}

(function() {
    let initTimer;

    function init() {
        document.querySelectorAll('.custom-select:not([data-cs-init])').forEach(el => {
            el.dataset.csInit = '1';
            try {
                new CustomSelect(el);
            } catch (e) {
                el.removeAttribute('data-cs-init');
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    const observer = new MutationObserver(() => {
        clearTimeout(initTimer);
        initTimer = setTimeout(() => init(), 50);
    });
    if (document.body) {
        observer.observe(document.body, { childList: true, subtree: true });
    } else {
        document.addEventListener('DOMContentLoaded', () => {
            observer.observe(document.body, { childList: true, subtree: true });
        });
    }
})();
