@once
<!-- LIVE_SYNC_ENGINE -->
<input type="hidden" id="live-sync-version-url" value="{{ route('live_sync.version') }}">
<script>
(function () {
    if (window.__liveSyncEngineInitialized) return;
    window.__liveSyncEngineInitialized = true;

    const POLL_INTERVAL_MS = 2500;
    const VERSION_ENDPOINT = (document.getElementById('live-sync-version-url')?.value || '/_live-sync/version');

    function deteksiScopeHalaman() {
        const path = (window.location.pathname || '/').replace(/\/+$/, '') || '/';
        const lower = path.toLowerCase();

        if (lower.includes('/dev-console')) return 'skip';
        if (lower.includes('/pengajuan')) return 'pengajuan';
        if (lower.includes('/riwayat-aktivitas')) return 'riwayat';
        if (lower.endsWith('/roles')) return 'roles';
        if (lower.endsWith('/divisi') || lower.endsWith('/jabatan')) return 'static';

        if (lower.includes('/tracking-pengadaan')) return 'tracking_pengadaan';
        if (lower.includes('/data-aset')) return 'data_aset';
        if (lower.includes('/barcode-aset')) return 'barcode';
        if (lower.includes('/laporan')) return 'laporan';
        if (lower.includes('/notifikasi')) return 'notifikasi';
        if (lower.includes('/kategori-aset')) return 'kategori';
        if (lower.endsWith('/cabang') || lower.includes('/master-cabang')) return 'cabang';
        if (lower.includes('/master-lokasi')) return 'lokasi';
        if (lower.includes('/kelola-akun')) return 'kelola_akun';
        if (lower === '/admin' || lower === '/masteradmin' || lower.endsWith('/dashboard')) return 'dashboard';

        return 'global';
    }

    const CURRENT_SCOPE = deteksiScopeHalaman();
    if (CURRENT_SCOPE === 'skip') return;

    const SKIP_MAIN_SYNC_SCOPES = new Set(['pengajuan', 'riwayat', 'roles', 'static']);

    let lastKnownVersion = null;
    let lastNotifCount = null;
    let isCheckingVersion = false;
    let isApplyingPageSync = false;
    let pendingSync = false;
    let timerId = null;

    function perbaruiBadgeSidebar(notifCount) {
        if (typeof notifCount !== 'number' || Number.isNaN(notifCount)) return;
        const link = document.querySelector('a.menu-item[href*="/notifikasi"], a.menu-item[data-menu-key="notifikasi"]');
        if (!link) return;

        let badge = link.querySelector('.notif-badge');
        if (notifCount > 0) {
            if (!badge) {
                badge = document.createElement('span');
                badge.className = 'notif-badge';
                link.appendChild(badge);
            }
            badge.textContent = String(notifCount);
            badge.title = notifCount + ' aset rusak atau mendekati jadwal servis';
            badge.style.display = '';
        } else if (badge) {
            badge.remove();
        }
    }

    function apakahUserSedangInteraksi() {
        if (document.body.classList.contains('is-updating-global')) return true;

        if (document.querySelector('.modal-backdrop.show, .sidebar-form-backdrop.show, [role="dialog"].show')) {
            return true;
        }
        if (document.querySelector('.chip-wrapper.is-editing, .chip-wrapper.active, .akun-item.is-editing')) {
            return true;
        }
        if (document.querySelector('.check-item-aset:checked')) {
            return true;
        }

        const activeEl = document.activeElement;
        if (activeEl && activeEl !== document.body) {
            const tag = (activeEl.tagName || '').toUpperCase();
            if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag) || activeEl.isContentEditable) {
                if (activeEl.closest('main')) {
                    const idLower = (activeEl.id || '').toLowerCase();
                    const allowWhileTypingInSearch =
                        (CURRENT_SCOPE === 'barcode' && idLower === 'input-search-barcode') ||
                        (CURRENT_SCOPE === 'notifikasi' && idLower === 'input-search-notifikasi') ||
                        (CURRENT_SCOPE === 'laporan' && idLower === 'filter-keyword-laporan');
                    if (!allowWhileTypingInSearch) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    function terapkanWarnaElemenNotifikasi(root) {
        const scopeEl = root || document;
        scopeEl.querySelectorAll('[data-bg]').forEach(function (el) {
            el.style.background = el.getAttribute('data-bg');
        });
        scopeEl.querySelectorAll('.notif-list [data-color]').forEach(function (el) {
            el.style.color = el.getAttribute('data-color');
        });
        scopeEl.querySelectorAll('[data-border-left]').forEach(function (el) {
            el.style.borderLeft = '5px solid ' + el.getAttribute('data-border-left');
        });
    }

    function sinkronkanDashboard(freshDoc) {
        const curMain = document.querySelector('main');
        const newMain = freshDoc.querySelector('main');
        if (!curMain || !newMain) return false;

        const prevSelectVal = document.getElementById('selectCabangRusak')?.value || 'semua';
        const prevScrolls = Array.from(curMain.querySelectorAll('.custom-scroll')).map(el => el.scrollTop);
        const winScrollY = window.scrollY;

        curMain.innerHTML = newMain.innerHTML;

        curMain.querySelectorAll('.custom-scroll').forEach((el, idx) => {
            if (typeof prevScrolls[idx] === 'number') el.scrollTop = prevScrolls[idx];
        });

        const sel = document.getElementById('selectCabangRusak');
        if (sel) {
            const hasOpt = Array.from(sel.options).some(o => o.value === prevSelectVal);
            sel.value = hasOpt ? prevSelectVal : 'semua';
            if (typeof filterCabangRusak === 'function') filterCabangRusak(sel.value);
        }
        if (typeof updateClock === 'function') updateClock();
        window.scrollTo(window.scrollX, winScrollY);
        return true;
    }

    function sinkronkanNotifikasi(freshDoc) {
        const curMain = document.querySelector('main');
        const newMain = freshDoc.querySelector('main');
        if (!curMain || !newMain) return false;

        ['.notif-ringkas', '.notif-info', '.navbar-date-pill'].forEach(selector => {
            const curEl = curMain.querySelector(selector);
            const newEl = newMain.querySelector(selector);
            if (curEl && newEl) curEl.innerHTML = newEl.innerHTML;
        });

        const curList = curMain.querySelector('.notif-list');
        const newList = newMain.querySelector('.notif-list');
        if (curList && newList) {
            curList.innerHTML = newList.innerHTML;
            terapkanWarnaElemenNotifikasi(curList);
        }

        const curPag = curMain.querySelector('.custom-pagination-wrap');
        const newPag = newMain.querySelector('.custom-pagination-wrap');
        if (curPag && newPag) {
            curPag.innerHTML = newPag.innerHTML;
        } else if (curPag && !newPag) {
            curPag.remove();
        } else if (!curPag && newPag) {
            curMain.appendChild(newPag.cloneNode(true));
        }

        const searchEl = document.getElementById('input-search-notifikasi');
        if (searchEl && (searchEl.value || '').trim() !== '') {
            searchEl.dispatchEvent(new Event('input'));
        }
        return true;
    }

    function sinkronkanTrackingPengadaan(freshDoc) {
        const curMain = document.querySelector('main');
        const newMain = freshDoc.querySelector('main');
        if (!curMain || !newMain) return false;

        const curKpi = curMain.querySelector('section.kpi-grid');
        const newKpi = newMain.querySelector('section.kpi-grid');
        if (curKpi && newKpi) curKpi.innerHTML = newKpi.innerHTML;

        ['inp-cabang', 'inp-kategori'].forEach(selId => {
            const curSel = document.getElementById(selId);
            const newSel = freshDoc.getElementById(selId);
            if (curSel && newSel && document.activeElement !== curSel) {
                const prev = curSel.value;
                curSel.innerHTML = newSel.innerHTML;
                if (Array.from(curSel.options).some(o => o.value === prev)) curSel.value = prev;
            }
        });

        const curBoxes = Array.from(curMain.querySelectorAll('section.card-box'));
        const newBoxes = Array.from(newMain.querySelectorAll('section.card-box'));
        curBoxes.forEach((box, idx) => {
            if (box.querySelector('#form-filter-tracking')) return;
            if (newBoxes[idx]) box.innerHTML = newBoxes[idx].innerHTML;
        });
        curMain.querySelectorAll('[data-bg]').forEach(function (el) {
            el.style.backgroundColor = el.getAttribute('data-bg');
        });
        return true;
    }

    function sinkronkanCabang(freshDoc) {
        if (document.querySelector('.chip-wrapper.is-editing, .chip-wrapper.active')) return false;
        const curCards = document.querySelectorAll('main .layout-grid .card-box');
        const newCards = freshDoc.querySelectorAll('main .layout-grid .card-box');
        if (curCards.length >= 2 && newCards.length >= 2) {
            curCards[1].innerHTML = newCards[1].innerHTML;
            return true;
        }
        return false;
    }

    function sinkronkanKelolaAkun(freshDoc) {
        if (document.querySelector('.akun-item.is-editing')) return false;
        const curCols = document.querySelectorAll('main .role-col');
        const newCols = freshDoc.querySelectorAll('main .role-col');
        if (curCols.length === 0 || curCols.length !== newCols.length) return false;

        curCols.forEach((col, idx) => {
            const curBoxes = col.querySelectorAll('.card-box');
            const newBoxes = newCols[idx].querySelectorAll('.card-box');
            if (curBoxes.length >= 2 && newBoxes.length >= 2) {
                curBoxes[1].innerHTML = newBoxes[1].innerHTML;
            }
        });
        return true;
    }

    function terapkanPembaruanHalaman(freshDoc) {
        if (CURRENT_SCOPE === 'laporan' && typeof window.__refreshLaporanLive === 'function') {
            return window.__refreshLaporanLive(freshDoc);
        }
        if (CURRENT_SCOPE === 'barcode' && typeof window.__refreshBarcodeLive === 'function') {
            return window.__refreshBarcodeLive(freshDoc);
        }
        if (CURRENT_SCOPE === 'data_aset' && typeof window.__refreshDataAsetLive === 'function') {
            return window.__refreshDataAsetLive(freshDoc);
        }
        if (CURRENT_SCOPE === 'kategori' && typeof window.__refreshKategoriLive === 'function') {
            return window.__refreshKategoriLive(freshDoc);
        }
        if (CURRENT_SCOPE === 'lokasi' && typeof window.__refreshLokasiLive === 'function') {
            return window.__refreshLokasiLive(freshDoc);
        }
        if (CURRENT_SCOPE === 'dashboard') {
            return sinkronkanDashboard(freshDoc);
        }
        if (CURRENT_SCOPE === 'notifikasi') {
            return sinkronkanNotifikasi(freshDoc);
        }
        if (CURRENT_SCOPE === 'tracking_pengadaan') {
            return sinkronkanTrackingPengadaan(freshDoc);
        }
        if (CURRENT_SCOPE === 'cabang') {
            return sinkronkanCabang(freshDoc);
        }
        if (CURRENT_SCOPE === 'kelola_akun') {
            return sinkronkanKelolaAkun(freshDoc);
        }
        return true;
    }

    async function ambilDanTerapkanHalaman() {
        if (isApplyingPageSync) return;
        if (apakahUserSedangInteraksi()) {
            pendingSync = true;
            return;
        }

        isApplyingPageSync = true;
        try {
            const url = new URL(window.location.href);
            url.searchParams.set('_live_sync_page', '1');
            url.searchParams.set('_t', String(Date.now()));

            const res = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                    'Cache-Control': 'no-cache'
                }
            });

            if (!res.ok || res.redirected && res.url.includes('/login')) {
                return;
            }

            const htmlText = await res.text();
            if (!htmlText || !htmlText.includes('<main')) return;

            if (apakahUserSedangInteraksi()) {
                pendingSync = true;
                return;
            }

            const parser = new DOMParser();
            const freshDoc = parser.parseFromString(htmlText, 'text/html');
            const applied = terapkanPembaruanHalaman(freshDoc);
            pendingSync = !applied;
        } catch (e) {
        } finally {
            isApplyingPageSync = false;
        }
    }

    async function cekVersiLiveSync(forceRefreshNow) {
        if (document.hidden && !forceRefreshNow) return;
        if (isCheckingVersion) return;

        if (pendingSync && !SKIP_MAIN_SYNC_SCOPES.has(CURRENT_SCOPE) && !apakahUserSedangInteraksi()) {
            pendingSync = false;
            await ambilDanTerapkanHalaman();
        }

        isCheckingVersion = true;
        try {
            const url = new URL(VERSION_ENDPOINT, window.location.origin);
            url.searchParams.set('scope', CURRENT_SCOPE);
            url.searchParams.set('_t', String(Date.now()));

            const res = await fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Cache-Control': 'no-cache'
                }
            });

            if (res.status === 401 || res.status === 419) {
                return;
            }
            if (!res.ok) return;

            const data = await res.json();
            if (!data || typeof data.version !== 'string') return;

            if (typeof data.notif_count === 'number' && data.notif_count !== lastNotifCount) {
                lastNotifCount = data.notif_count;
                perbaruiBadgeSidebar(data.notif_count);
            }

            if (lastKnownVersion === null) {
                lastKnownVersion = data.version;
                return;
            }

            if (data.version !== lastKnownVersion) {
                lastKnownVersion = data.version;
                if (!SKIP_MAIN_SYNC_SCOPES.has(CURRENT_SCOPE)) {
                    await ambilDanTerapkanHalaman();
                }
            }
        } catch (e) {
        } finally {
            isCheckingVersion = false;
        }
    }

    window.__jalankanLiveSyncSekarang = function () {
        pendingSync = true;
        return cekVersiLiveSync(true);
    };

    document.addEventListener('DOMContentLoaded', function () {
        cekVersiLiveSync(false);
        timerId = setInterval(function () {
            cekVersiLiveSync(false);
        }, POLL_INTERVAL_MS);
    });

    document.addEventListener('visibilitychange', function () {
        if (!document.hidden) {
            cekVersiLiveSync(true);
        }
    });
})();
</script>
@endonce
