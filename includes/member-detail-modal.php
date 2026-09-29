<?php
/**
 * Mitglieder-Detail-Modal (Foto, Funktionen, Kontakt, Bio), das beim Klick
 * auf eine Mitglieder-Kachel oder einen Organigramm-Posten aufgeht.
 *
 * Erwartet die Variable $membersJson (siehe config/members.php,
 * buildMembersJson()) im aufrufenden Scope.
 */
?>
<div class="member-modal-overlay" id="memberModal">
    <div class="member-modal">
        <button class="member-modal-close" onclick="closeMemberModal()" title="Schließen">&times;</button>
        <div class="member-modal-header">
            <div id="modalPhotoWrap"></div>
            <div class="member-modal-name" id="modalName"></div>
            <div class="member-modal-function" id="modalFunction"></div>
            <div class="member-modal-rank" id="modalRank" style="display:none;">
                <img id="modalRankBadge" src="" alt="">
                <span id="modalRankText"></span>
            </div>
            <div class="member-modal-badges" id="modalBadges"></div>
        </div>
        <div class="member-modal-body" id="modalBody"></div>
    </div>
</div>

<script>
var memberData = <?php echo json_encode($membersJson, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>;

function showMemberDetail(id) {
    var m = memberData[id];
    if (!m) return;

    // Photo
    var photoWrap = document.getElementById('modalPhotoWrap');
    if (m.photo) {
        photoWrap.innerHTML = '<img src="' + m.photo + '" alt="' + m.name + '" class="member-modal-photo">';
    } else {
        photoWrap.innerHTML = '<div class="member-modal-placeholder"><i class="fas fa-user"></i></div>';
    }

    // Name & Functions
    document.getElementById('modalName').textContent = m.name;
    var funcEl = document.getElementById('modalFunction');
    // Funktionen stehen übersichtlich als Liste im Detailbereich (nicht hier)
    funcEl.textContent = '';
    funcEl.style.display = 'none';

    // Rank
    var rankEl = document.getElementById('modalRank');
    if (m.rank) {
        document.getElementById('modalRankBadge').src = m.rankBadge;
        document.getElementById('modalRankText').textContent = m.rank + ' – ' + m.rankName;
        rankEl.style.display = 'inline-flex';
    } else {
        rankEl.style.display = 'none';
    }

    // Verwendungs-/Funktionsabzeichen
    var badgesEl = document.getElementById('modalBadges');
    if (m.badges && m.badges.length > 0) {
        badgesEl.innerHTML = m.badges.map(function(b) {
            return '<img class="badge-inline-img badge-modal-img" src="' + b.image + '" alt="' + escHtml(b.code) + '" title="' + escHtml(b.name) + '">';
        }).join('');
        badgesEl.style.display = 'flex';
    } else {
        badgesEl.innerHTML = '';
        badgesEl.style.display = 'none';
    }

    // Body details
    var body = document.getElementById('modalBody');
    var html = '';
    var hasDetails = m.group || m.entry_date || m.phone || m.email || m.bio || (m.functions && m.functions.length > 0);

    if (hasDetails) {
        html += '<ul class="member-modal-details">';
        if (m.group) {
            html += '<li><i class="fas fa-users"></i> ' + escHtml(m.group) + '</li>';
        }
        if (m.functions && m.functions.length > 0) {
            html += '<li class="member-modal-funcs"><i class="fas fa-briefcase"></i><div><strong>Funktionen</strong><ul>' + m.functions.map(function (f) { return '<li>' + escHtml(f) + '</li>'; }).join('') + '</ul></div></li>';
        }
        if (m.entry_date) {
            html += '<li><i class="fas fa-calendar-alt"></i> Eintritt: ' + escHtml(m.entry_date) + '</li>';
        }
        if (m.phone) {
            html += '<li><i class="fas fa-phone"></i> <a href="tel:' + escHtml(m.phone) + '">' + escHtml(m.phone) + '</a></li>';
        }
        if (m.email) {
            html += '<li><i class="fas fa-envelope"></i> <a href="mailto:' + escHtml(m.email) + '">' + escHtml(m.email) + '</a></li>';
        }
        html += '</ul>';
        if (m.bio) {
            html += '<div class="member-modal-bio">' + escHtml(m.bio) + '</div>';
        }
    } else {
        html = '<div class="member-modal-empty"><i class="fas fa-info-circle"></i> Keine weiteren Details hinterlegt.</div>';
    }

    body.innerHTML = html;

    document.getElementById('memberModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMemberModal() {
    document.getElementById('memberModal').classList.remove('active');
    document.body.style.overflow = '';
}

// Close on overlay click
document.getElementById('memberModal').addEventListener('click', function(e) {
    if (e.target === this) closeMemberModal();
});

// Close on Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeMemberModal();
});

function escHtml(str) {
    var div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
}
</script>
