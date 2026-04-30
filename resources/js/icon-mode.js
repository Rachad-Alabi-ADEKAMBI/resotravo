const FA_ICON_MAP = {
    "%": "fa-percent",
    "🔧": "fa-wrench",
    "🔨": "fa-hammer",
    "🔍": "fa-magnifying-glass",
    "📭": "fa-inbox",
    "📋": "fa-clipboard-list",
    "⏳": "fa-hourglass-half",
    "✅": "fa-circle-check",
    "❌": "fa-circle-xmark",
    "🔒": "fa-lock",
    "⚙️": "fa-gear",
    "⚠️": "fa-triangle-exclamation",
    "💰": "fa-sack-dollar",
    "🏦": "fa-building-columns",
    "📊": "fa-chart-column",
    "👷": "fa-helmet-safety",
    "💡": "fa-lightbulb",
    "📦": "fa-box",
    "📂": "fa-folder-open",
    "📎": "fa-paperclip",
    "📷": "fa-camera",
    "📄": "fa-file-lines",
    "⚖️": "fa-scale-balanced",
    "🎓": "fa-graduation-cap",
    "🪪": "fa-id-card",
    "😔": "fa-face-frown",
    "🪛": "fa-screwdriver-wrench",
    "🏠": "fa-house",
    "🚿": "fa-shower",
    "⚡": "fa-bolt",
    "🧱": "fa-trowel-bricks",
    "🪚": "fa-saw",
    "🖌️": "fa-paint-roller",
    "🌿": "fa-leaf",
    "🚚": "fa-truck",
};

const NORMALIZED_FA_ICON_MAP = Object.fromEntries(
    Object.entries(FA_ICON_MAP).map(([icon, iconClass]) => [normalizeIconText(icon), iconClass])
);

function normalizeIconText(value) {
    return (value || "").replace(/\uFE0F/g, "").trim();
}

function getFaClass(text) {
    const raw = (text || "").trim();
    return FA_ICON_MAP[raw] || NORMALIZED_FA_ICON_MAP[normalizeIconText(raw)] || null;
}

function canReplaceIconElement(element) {
    if (!element || element.dataset.faIconApplied === "1") return false;
    if (!/\bicon\b|icon-|Icon/.test(element.className || "")) return false;
    if (element.querySelector("input, textarea, select, img, svg, i")) return false;

    const text = element.textContent.trim();
    if (!text) return false;

    return Boolean(getFaClass(text));
}

function replaceIconElement(element) {
    const iconClass = getFaClass(element.textContent);
    if (!iconClass) return;

    element.dataset.originalIcon = element.textContent.trim();
    element.dataset.faIconApplied = "1";
    element.innerHTML = `<i class="fa-solid ${iconClass}" aria-hidden="true"></i>`;
}

export function applyIconMode() {
    if (window.MESOTRAVO_ICON_MODE !== "fontawesome") return;

    document.querySelectorAll('[class*="icon"], [class*="Icon"]').forEach((element) => {
        if (canReplaceIconElement(element)) replaceIconElement(element);
    });
}

export function initIconMode() {
    applyIconMode();

    if (window.MESOTRAVO_ICON_MODE !== "fontawesome") return;

    const observer = new MutationObserver(() => applyIconMode());
    observer.observe(document.body, { childList: true, subtree: true });
}
