<?php
$css = "
/* --- MODERN TOAST NOTIFICATIONS --- */
#toast-container {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 10px;
    pointer-events: none;
}

.toast {
    background: rgba(15, 23, 42, 0.95);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border-left: 4px solid var(--accent-color);
    color: var(--text-primary);
    padding: 16px 24px;
    border-radius: 8px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    gap: 12px;
    transform: translateX(120%);
    opacity: 0;
    transition: transform 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55), opacity 0.4s ease;
    min-width: 280px;
    pointer-events: auto;
}

.toast.show {
    transform: translateX(0);
    opacity: 1;
}

.toast i {
    font-size: 1.2rem;
}

.toast.success {
    border-left-color: var(--success-color);
}
.toast.success i {
    color: var(--success-color);
}

.toast.error {
    border-left-color: var(--error-color);
}
.toast.error i {
    color: var(--error-color);
}
";
file_put_contents('public/assets/css/dashboard.css', $css, FILE_APPEND);
echo "Appended toast CSS.";
