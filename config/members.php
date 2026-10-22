<?php
/**
 * Aufbereitung der aktiven Mitglieder als JSON für die Detail-Modals auf
 * den "Über uns"-Unterseiten (Mannschaft, Ausschuss & Organigramm). Beide
 * Seiten können sich gegenseitig referenzieren (z.B. ein Organigramm-Posten
 * auf der Ausschuss-Seite, der eigentlich ein Mannschaftsmitglied ist),
 * deshalb liefert diese Funktion immer ALLE aktiven Mitglieder.
 */

require_once __DIR__ . '/badges.php';
require_once __DIR__ . '/ranks.php';
require_once __DIR__ . '/i18n.php';

/**
 * Liefert die passende Sprachfassung der Funktionen (JSON-Array mit
 * section/role) eines Mitglieds - Englisch nur, wenn umgeschaltet UND eine
 * automatische Übersetzung vorhanden ist (siehe config/translate.php),
 * sonst immer die deutsche Fassung.
 */
function getLocalizedFunctions(array $member): array {
    if (isEnglish() && !empty($member['functions_en'])) {
        $decoded = json_decode($member['functions_en'], true);
        if (is_array($decoded)) return $decoded;
    }
    return json_decode($member['functions'] ?? '[]', true) ?: [];
}

/**
 * Wie getLocalizedFunctions(), aber für die Bio/Beschreibung.
 */
function getLocalizedBio(array $member): string {
    if (isEnglish() && !empty($member['bio_en'])) {
        return $member['bio_en'];
    }
    return $member['bio'] ?? '';
}

/**
 * Papierkorb für Mitglieder: "Löschen" verschiebt ein Mitglied nur in den
 * Papierkorb (deleted_at wird gesetzt, active auf 0), statt es sofort aus
 * der Datenbank zu entfernen. Von dort kann es wiederhergestellt werden;
 * nach 24 Stunden im Papierkorb wird es automatisch endgültig gelöscht
 * (siehe purgeExpiredDeletedMembers(), aufgerufen bei jedem Aufruf von
 * Admin -> Mitglieder).
 */
function softDeleteMember(PDO $db, int $id): void {
    $db->prepare("UPDATE members SET active = 0, deleted_at = NOW() WHERE id = ?")->execute([$id]);
}

function restoreMember(PDO $db, int $id): void {
    $db->prepare("UPDATE members SET active = 1, deleted_at = NULL WHERE id = ?")->execute([$id]);
}

/**
 * Löscht ein Mitglied (inkl. Foto) endgültig aus der Datenbank.
 */
function hardDeleteMember(PDO $db, int $id): void {
    $stmt = $db->prepare("SELECT photo FROM members WHERE id = ?");
    $stmt->execute([$id]);
    $photo = $stmt->fetchColumn();
    if ($photo) {
        $path = UPLOAD_PATH . $photo;
        if (file_exists($path)) unlink($path);
    }
    $db->prepare("DELETE FROM members WHERE id = ?")->execute([$id]);
}

/**
 * Löscht jedes Mitglied endgültig, das seit mehr als 24 Stunden im
 * Papierkorb liegt - wird bei jedem Aufruf von Admin -> Mitglieder
 * nebenbei ausgeführt, kein separater Cronjob nötig.
 */
function purgeExpiredDeletedMembers(PDO $db): void {
    $ids = $db->query("SELECT id FROM members WHERE deleted_at IS NOT NULL AND deleted_at < DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($ids as $id) {
        hardDeleteMember($db, (int) $id);
    }
}

function buildMembersJson(PDO $db): array {
    $allMembersStmt = $db->query("SELECT id, firstname, lastname, rank, functions, functions_en, badge1, badge2, group_name, photo, entry_date, phone, email, bio, bio_en FROM members WHERE active = 1");
    $allMembers = $allMembersStmt->fetchAll();
    $membersJson = [];
    foreach ($allMembers as $am) {
        $funcs = getLocalizedFunctions($am);
        $funcLabels = array_map(fn($f) => $f['role'] . ' (' . t('members.section.' . $f['section']) . ')', $funcs);
        $badgeList = [];
        foreach ([$am['badge1'] ?? null, $am['badge2'] ?? null] as $bc) {
            if ($bc) $badgeList[] = ['code' => $bc, 'name' => getBadgeName($bc), 'color' => getBadgeColor($bc), 'image' => getBadgeImage($bc)];
        }
        $showRank = false; // Ränge werden öffentlich nirgends mehr angezeigt
        $membersJson[$am['id']] = [
            'name' => $am['firstname'] . ' ' . $am['lastname'],
            'photo' => $am['photo'] ? 'uploads/' . $am['photo'] : '',
            'rank' => $showRank ? $am['rank'] : '',
            'rankName' => ($showRank && $am['rank']) ? getRankName($am['rank']) : '',
            'rankBadge' => ($showRank && $am['rank']) ? getRankBadgePath($am['rank']) : '',
            'badges' => $badgeList,
            'functions' => $funcLabels,
            'group' => t('members.group.' . $am['group_name']),
            'entry_date' => $am['entry_date'] ?? '',
            'phone' => $am['phone'] ?? '',
            'email' => $am['email'] ?? '',
            'bio' => getLocalizedBio($am),
        ];
    }
    return $membersJson;
}

/**
 * Rendert eine Mitgliedergruppen-Kachel (Kommando, Ausschuss, Mannschaft
 * oder Ehrenmitglieder) mit Foto-Grid und Flip-Karten. Wird von
 * pages/mannschaft.php (Kommando, Mannschaft, Ehrenmitglieder, Jugend) und
 * pages/ausschuss.php (Ausschuss) verwendet.
 */
function renderUeberUnsGroupCard(PDO $db, string $group, array $groupIcons, array $groupDescriptions): void {
    // Kommando/Ausschuss: members who have this section in their JSON functions
    // Mannschaft/Ehrenmitglieder: members with this as group_name
    if (in_array($group, ['Kommando', 'Ausschuss'])) {
        $pattern = '%"section":"' . $group . '"%';
        $stmt = $db->prepare("SELECT * FROM members WHERE active = 1 AND functions LIKE ? ORDER BY sort_order, lastname");
        $stmt->execute([$pattern]);
    } else {
        $stmt = $db->prepare("SELECT * FROM members WHERE group_name = ? AND active = 1 ORDER BY sort_order, lastname");
        $stmt->execute([$group]);
    }
    $groupMembers = $stmt->fetchAll();
    ?>
    <div class="content-card" id="<?php echo strtolower(str_replace(' ', '', $group)); ?>">
        <div class="content-card-header">
            <div class="content-card-icon"><i class="fas <?php echo $groupIcons[$group] ?? 'fa-users'; ?>"></i></div>
            <h2><?php echo e(t('members.group.' . $group)); ?> <span class="member-count"><?php echo count($groupMembers); ?></span></h2>
        </div>
        <div class="content-card-body">
            <p><?php echo htmlspecialchars($groupDescriptions[$group] ?? ''); ?></p>

            <?php if ($group === 'Ausschuss'): ?>
                <img src="assets/images/ausschuss_gruppenbild.jpg" alt="Der Ausschuss der FF Reichenau" class="content-image ausschuss-gruppenbild" data-lightbox-group="ausschuss-gruppenbild">
            <?php endif; ?>

            <?php if (!empty($groupMembers)): ?>
                <?php $gridClass = ['Kommando' => 'grid-kommando', 'Ausschuss' => 'grid-ausschuss', 'Mannschaft' => 'grid-static', 'Ehrenmitglieder' => 'grid-static'][$group] ?? ''; ?>
                <div class="members-public-grid <?php echo $gridClass; ?>">
                    <?php foreach ($groupMembers as $m): ?>
                        <?php
                        // Get the role for this specific section
                        $funcs = getLocalizedFunctions($m);
                        $roleInSection = '';
                        foreach ($funcs as $f) {
                            if (($f['section'] ?? '') === $group) {
                                $roleInSection = $f['role'] ?? '';
                                break;
                            }
                        }
                        // Alle Funktionen (auch aus anderen Bereichen wie "Beauftragter" oder
                        // "Sonstige") für die Kachel-Rückseite - nicht nur die zur aktuellen
                        // Kartengruppe passende, damit z.B. Beauftragte-Rollen bei der
                        // Mannschaft nicht unter den Tisch fallen.
                        $allFuncLabels = array_map(fn($f) => trim(($f['role'] ?? '') . ' (' . t('members.section.' . ($f['section'] ?? '')) . ')'), $funcs);
                        ?>
                        <div class="member-public-card" data-member-id="<?php echo (int)$m['id']; ?>"<?php if (!in_array($group, ['Mannschaft', 'Ehrenmitglieder'], true)): ?> onclick="showMemberDetail(<?php echo (int)$m['id']; ?>)"<?php endif; ?>>
                            <div class="member-card-flip">
                                <div class="member-card-face member-card-front">
                                    <?php if ($m['photo']): ?>
                                        <img src="uploads/<?php echo htmlspecialchars($m['photo']); ?>"
                                             alt="<?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?>"
                                             class="member-public-photo">
                                    <?php else: ?>
                                        <div class="member-public-placeholder">
                                            <i class="fas fa-user"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="member-public-info">
                                        <strong><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                        <?php $isStaticGroup = in_array($group, ["Mannschaft", "Ehrenmitglieder"], true); ?>
                                        <?php if ($roleInSection && !$isStaticGroup): ?>
                                            <span class="member-public-function"><?php echo htmlspecialchars($roleInSection); ?></span>
                                        <?php endif; ?>
                                        <?php foreach ($isStaticGroup ? [] : [$m['badge1'] ?? null, $m['badge2'] ?? null] as $bc): ?>
                                            <?php if ($bc): ?>
                                                <img src="<?php echo htmlspecialchars(getBadgeImage($bc)); ?>" alt="<?php echo htmlspecialchars($bc); ?>" class="badge-inline-img" title="<?php echo htmlspecialchars(getBadgeName($bc)); ?>">
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <?php
                                $backShowRank = false; // keine Rang-Anzeige
                                $backBadgeNames = [];
                                foreach ([$m['badge1'] ?? null, $m['badge2'] ?? null] as $bc) {
                                    if ($bc) $backBadgeNames[] = getBadgeName($bc);
                                }
                                $localizedBio = getLocalizedBio($m);
                                $hasBackDetails = !empty($allFuncLabels) || $backShowRank || !empty($backBadgeNames) || !empty($m['entry_date']) || !empty($m['phone']) || !empty($m['email']) || !empty($localizedBio);
                                ?>
                                <div class="member-card-face member-card-back">
                                    <?php if ($hasBackDetails): ?>
                                        <strong class="member-card-back-name"><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                        <?php if (!empty($allFuncLabels)): ?>
                                            <?php foreach ($allFuncLabels as $fl): ?><span class="member-card-back-line member-card-back-functions"><i class="fas fa-briefcase"></i> <?php echo htmlspecialchars($fl); ?></span><?php endforeach; ?>
                                        <?php endif; ?>
                                        <?php if ($backShowRank): ?>
                                            <span class="member-card-back-line"><i class="fas fa-star"></i> <?php echo htmlspecialchars($m['rank'] . ' – ' . getRankName($m['rank'])); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($backBadgeNames)): ?>
                                            <span class="member-card-back-line"><i class="fas fa-award"></i> <?php echo htmlspecialchars(implode(', ', $backBadgeNames)); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($m['entry_date'])): ?>
                                            <span class="member-card-back-line"><i class="fas fa-calendar-alt"></i> <?php echo e(t('members.since')); ?> <?php echo htmlspecialchars($m['entry_date']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($m['phone'])): ?>
                                            <span class="member-card-back-line"><i class="fas fa-phone"></i> <?php echo htmlspecialchars($m['phone']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($m['email'])): ?>
                                            <span class="member-card-back-line"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($m['email']); ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($localizedBio)): ?>
                                            <p class="member-card-back-bio"><?php echo htmlspecialchars(mb_substr($localizedBio, 0, 90)) . (mb_strlen($localizedBio) > 90 ? '…' : ''); ?></p>
                                        <?php endif; ?>
                                        <span class="member-card-back-more"><i class="fas fa-circle-info"></i> <?php echo e(t('members.click_for_more')); ?></span>
                                    <?php else: ?>
                                        <div class="member-card-back-emptystate">
                                            <i class="fas fa-fire-extinguisher"></i>
                                            <strong><?php echo htmlspecialchars($m['firstname'] . ' ' . $m['lastname']); ?></strong>
                                            <span><?php echo e(t('members.active_member_of')); ?><br><?php echo e(t('members.group.' . $group)); ?></span>
                                        </div>
                                        <span class="member-card-back-more"><i class="fas fa-circle-info"></i> <?php echo e(t('members.click_for_more')); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted-public"><em><?php echo e(t('members.coming_soon')); ?></em></p>
            <?php endif; ?>
        </div>
    </div>
    <?php
}
