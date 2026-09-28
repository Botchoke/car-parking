/* ============================================
   USER SIDE SCRIPTS
   ============================================ */

document.addEventListener("DOMContentLoaded", function () {

    // Animated counters (for any .counter elements)
    const counters = document.querySelectorAll(".counter");
    counters.forEach(counter => {
        const target = Number(counter.getAttribute("data-target"));
        let count = 0;
        const updateCounter = () => {
            if (count < target) {
                count++;
                counter.innerText = count;
                setTimeout(updateCounter, 20);
            } else {
                counter.innerText = target;
            }
        };
        updateCounter();
    });

    // Show toast if success params exist in URL
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('parked') === 'success') {
        showToast('Thank you for choosing Hypercar Parking! Your session has started.', 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    if (urlParams.get('reserved') === 'success') {
        showToast('Reservation submitted! Please wait for admin approval.', 'info');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    if (urlParams.get('added') === 'success') {
        showToast('Vehicle added successfully!', 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    if (urlParams.get('deleted') === 'success') {
        showToast('Vehicle removed.', 'success');
        window.history.replaceState({}, document.title, window.location.pathname);
    }

    if (urlParams.get('error')) {
        showToast(decodeURIComponent(urlParams.get('error')), 'info');
        window.history.replaceState({}, document.title, window.location.pathname);
    }
});

/* Toast notification helper */
function showToast(message, type = 'success') {
    const toast = document.getElementById('successToast');
    if (!toast) return;
    const toastMsg = document.getElementById('toastMessage');
    toastMsg.textContent = message;
    toast.className = 'notification-toast ' + type;
    toast.classList.add('show');
    setTimeout(() => toast.classList.remove('show'), 4500);
}

/* Live active-session timer (used in active_session.php and dashboard) */
function updateUserTimer() {
    const timers = document.querySelectorAll(".session-timer");
    timers.forEach(el => {
        const rawTime = el.dataset.time;
        if (!rawTime) return;
        const start = new Date(rawTime.replace(" ", "T"));
        const now = new Date();
        let diff = Math.floor((now - start) / 1000);
        if (isNaN(diff) || diff < 0) diff = 0;

        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        el.innerText = `${h}h ${m}m ${s}s`;
    });
}
setInterval(updateUserTimer, 1000);
updateUserTimer();