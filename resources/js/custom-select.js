class CustomSelect {
    constructor(el) {
        this.wrapper = el;
        this.trigger = el.querySelector('[data-cs-trigger]');
        this.display = el.querySelector('[data-cs-display]');
        this.dropdown = el.querySelector('[data-cs-dropdown]');
        this.search = el.querySelector('[data-cs-search]');
        this.optionsContainer = el.querySelector('[data-cs-options]');
        this.options = [...el.querySelectorAll('[data-cs-option]')];
        this.select = el.querySelector('select');
        this.isOpen = false;

        this._init();
    }

    _init() {
        this._syncDisplay();
        this._onTriggerClick = (e) => { e.stopPropagation(); this._toggle(); };
        this._onDocClick = (e) => { if (!this.wrapper.contains(e.target)) this._close(); };
        this._onKeydown = (e) => { if (e.key === 'Escape') this._close(); if (e.key === 'Tab') this._close(); };
        this._onSearchInput = () => this._filter();
        this._onSelectChange = () => this._syncDisplay();

        this.trigger.addEventListener('click', this._onTriggerClick);
        document.addEventListener('click', this._onDocClick);
        document.addEventListener('keydown', this._onKeydown);
        this.select.addEventListener('change', this._onSelectChange);

        this.options.forEach(opt => {
            opt.addEventListener('click', (e) => {
                e.stopPropagation();
                this._select(opt.dataset.csValue, opt.dataset.csLabel);
            });
        });

        if (this.search) {
            this.search.addEventListener('input', this._onSearchInput);
            this.search.addEventListener('click', (e) => e.stopPropagation());
            this.search.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    const visible = this.options.filter(o => !o.classList.contains('hidden'));
                    if (visible.length === 1) {
                        this._select(visible[0].dataset.csValue, visible[0].dataset.csLabel);
                    }
                }
            });
        }
    }

    _toggle() { this.isOpen ? this._close() : this._open(); }

    _open() {
        this.isOpen = true;
        this.dropdown.classList.remove('hidden');
        this.wrapper.classList.add('cs-open');
        if (this.search) { setTimeout(() => this.search.focus(), 50); }
    }

    _close() {
        this.isOpen = false;
        this.dropdown.classList.add('hidden');
        this.wrapper.classList.remove('cs-open');
        if (this.search) {
            this.search.value = '';
            this._filter();
        }
    }

    _filter() {
        const q = (this.search?.value || '').toLowerCase();
        this.options.forEach(opt => {
            const label = (opt.dataset.csLabel || '').toLowerCase();
            opt.classList.toggle('hidden', !label.includes(q));
        });
    }

    _select(val, label) {
        this.select.value = val;
        this.select.dispatchEvent(new Event('change', { bubbles: true }));
        this.select.dispatchEvent(new Event('input', { bubbles: true }));
        this.display.textContent = label;
        this.options.forEach(opt => opt.classList.toggle('cs-selected', opt.dataset.csValue === val));
        this._close();
    }

    _syncDisplay() {
        const selected = this.select.options[this.select.selectedIndex];
        if (selected) {
            this.display.textContent = selected.text;
            this.options.forEach(opt => opt.classList.toggle('cs-selected', opt.dataset.csValue === selected.value));
        }
    }

    destroy() {
        this.trigger.removeEventListener('click', this._onTriggerClick);
        document.removeEventListener('click', this._onDocClick);
        document.removeEventListener('keydown', this._onKeydown);
        this.select.removeEventListener('change', this._onSelectChange);
        if (this.search) this.search.removeEventListener('input', this._onSearchInput);
    }
}

(function() {
    function init() {
        document.querySelectorAll('.custom-select:not([data-cs-init])').forEach(el => {
            el.dataset.csInit = '1';
            new CustomSelect(el);
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    const observer = new MutationObserver(() => init());
    observer.observe(document.body || document.documentElement, { childList: true, subtree: true });
})();
