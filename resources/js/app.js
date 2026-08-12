// import './bootstrap';

import Alpine from 'alpinejs';

const normalizeTheme = (value) => (value === 'dark' ? 'dark' : 'light');

const readStoredTheme = () => {
    const raw = localStorage.getItem('gitradar-theme')
        || localStorage.getItem('devscore-theme')
        || 'light';

    return normalizeTheme(raw);
};

const applyTheme = (pref) => {
    const theme = normalizeTheme(pref);

    try {
        localStorage.setItem('gitradar-theme', theme);
        document.documentElement.setAttribute('data-theme-pref', theme);
        document.documentElement.classList.toggle('dark', theme === 'dark');
    } catch (e) {
        /* localStorage unavailable */
    }

    return theme;
};

document.addEventListener('alpine:init', () => {
    Alpine.store('theme', {
        pref: applyTheme(readStoredTheme()),

        toggle() {
            this.pref = applyTheme(this.pref === 'dark' ? 'light' : 'dark');
        },

        set(theme) {
            this.pref = applyTheme(theme);
        },
    });

    Alpine.data('themeToggle', () => ({
        get pref() {
            return this.$store.theme.pref;
        },

        toggle() {
            this.$store.theme.toggle();
        },

        set(theme) {
            this.$store.theme.set(theme);
        },
    }));
});

window.Alpine = Alpine;

Alpine.start();
