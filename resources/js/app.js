import { createPopper } from "@popperjs/core";
import Alpine from "alpinejs";
import "./bootstrap";
import initCharts from "./charts";
import registerUi from "./ui";

window.Alpine = Alpine;
window.createPopper = createPopper;

// Register Alpine.js components before initializing
Alpine.data("dropdown", () => ({
    open: false,
    toggle() {
        this.open = !this.open;
        if (this.open) this.position();
    },
    position() {
        this.$nextTick(() => {
            const button = this.$el;
            const dropdown = this.$refs.dropdown;
            const rect = button.getBoundingClientRect();

            // Apply initial styles to ensure measurement is possible
            dropdown.style.position = "fixed";
            dropdown.style.zIndex = "999";

            const isRtl = document.documentElement.getAttribute("dir") === "rtl";
            if (isRtl) {
                dropdown.style.left = `${rect.left}px`;
                dropdown.style.right = "auto";
            } else {
                dropdown.style.right = `${window.innerWidth - rect.right - 13}px`;
                dropdown.style.left = "auto";
            }

            const dropdownHeight = Math.max(dropdown.offsetHeight, 0);
            const spaceBelow = window.innerHeight - rect.bottom;
            const spaceAbove = rect.top;

            if (spaceBelow < dropdownHeight && spaceAbove > spaceBelow) {
                dropdown.style.top = `${rect.top - dropdownHeight}px`;
            } else {
                dropdown.style.top = `${rect.bottom}px`;
            }
        });
    },
    init() {
        this.$watch("open", (value) => {
            if (value) this.position();
        });
    },
}));

registerUi(Alpine);

Alpine.start();


document.addEventListener("DOMContentLoaded", () => {
    initCharts();

    // tombol salin: <button data-copy="teks"> atau data-copy-target="#id"
    document.querySelectorAll("[data-copy], [data-copy-target]").forEach((btn) => {
        btn.addEventListener("click", async () => {
            const target = btn.dataset.copyTarget ? document.querySelector(btn.dataset.copyTarget) : null;
            const text = target ? (target.value ?? target.textContent) : btn.dataset.copy;
            try { await navigator.clipboard.writeText(text.trim()); } catch (e) { return; }
            const old = btn.textContent;
            btn.textContent = "Tersalin ✓";
            setTimeout(() => (btn.textContent = old), 1500);
        });
    });

    // konfirmasi aksi berbahaya: <form data-confirm="Yakin?">
    document.querySelectorAll("form[data-confirm]").forEach((f) =>
        f.addEventListener("submit", (e) => { if (!confirm(f.dataset.confirm)) e.preventDefault(); })
    );
});
