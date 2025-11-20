<?php
/**
 * API: Automatyczna aktualizacja projektu z GitHub
 */

require_once __DIR__ . '/common.php';

$action = $_GET['action'] ?? $_POST['action'] ?? 'check';

try {
    // Sprawdź czy git jest dostępny
    if (!function_exists('exec')) {
        throw new Exception('Funkcja exec() jest wyłączona');
    }

    exec('which git 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Git nie jest zainstalowany');
    }

    // Zmień katalog na główny folder projektu
    $projectPath = ROOT_PATH;
    chdir($projectPath);

    switch ($action) {
        case 'check':
            // Sprawdź czy są dostępne aktualizacje
            $updateInfo = checkForUpdates();
            echo json_encode([
                'success' => true,
                'updates_available' => $updateInfo['has_updates'],
                'current_branch' => $updateInfo['current_branch'],
                'local_commit' => $updateInfo['local_commit'],
                'remote_commit' => $updateInfo['remote_commit'],
                'commits_behind' => $updateInfo['commits_behind'],
                'commit_messages' => $updateInfo['commit_messages'] ?? []
            ]);
            break;

        case 'update':
            // Pobierz i zastosuj aktualizacje
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Metoda niedozwolona - użyj POST');
            }

            $result = performUpdate();
            echo json_encode([
                'success' => true,
                'updated' => $result['updated'],
                'message' => $result['message'],
                'new_commit' => $result['new_commit'] ?? null,
                'files_changed' => $result['files_changed'] ?? []
            ]);
            break;

        case 'status':
            // Sprawdź status git
            $status = getGitStatus();
            echo json_encode([
                'success' => true,
                'branch' => $status['branch'],
                'clean' => $status['clean'],
                'uncommitted_changes' => $status['uncommitted_changes']
            ]);
            break;

        default:
            throw new Exception('Nieznana akcja: ' . $action);
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Sprawdza czy są dostępne aktualizacje
 */
function checkForUpdates(): array
{
    // Pobierz aktualną gałąź
    exec('git rev-parse --abbrev-ref HEAD 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Nie można pobrać nazwy gałęzi');
    }
    $currentBranch = trim($output[0]);

    // Pobierz lokalny commit hash
    exec('git rev-parse HEAD 2>&1', $output, $returnCode);
    $localCommit = trim(end($output));

    // Fetch latest changes (bez merge)
    exec('git fetch origin 2>&1', $output, $returnCode);
    if ($returnCode !== 0) {
        throw new Exception('Nie można pobrać zmian z remote: ' . implode("\n", $output));
    }

    // Pobierz remote commit hash
    exec("git rev-parse origin/$currentBranch 2>&1", $output, $returnCode);
    if ($returnCode !== 0) {
        // Branch może nie istnieć na remote
        return [
            'has_updates' => false,
            'current_branch' => $currentBranch,
            'local_commit' => substr($localCommit, 0, 7),
            'remote_commit' => 'N/A',
            'commits_behind' => 0
        ];
    }
    $remoteCommit = trim(end($output));

    // Sprawdź ile commitów w tyle
    exec("git rev-list --count HEAD..origin/$currentBranch 2>&1", $output, $returnCode);
    $commitsBehind = $returnCode === 0 ? (int)trim(end($output)) : 0;

    // Pobierz listę nowych commitów
    $commitMessages = [];
    if ($commitsBehind > 0) {
        exec("git log HEAD..origin/$currentBranch --pretty=format:'%h|%s|%ar' --max-count=10 2>&1", $output, $returnCode);
        if ($returnCode === 0) {
            foreach ($output as $line) {
                $parts = explode('|', $line, 3);
                if (count($parts) === 3) {
                    $commitMessages[] = [
                        'hash' => $parts[0],
                        'message' => $parts[1],
                        'time' => $parts[2]
                    ];
                }
            }
        }
    }

    return [
        'has_updates' => $localCommit !== $remoteCommit && $commitsBehind > 0,
        'current_branch' => $currentBranch,
        'local_commit' => substr($localCommit, 0, 7),
        'remote_commit' => substr($remoteCommit, 0, 7),
        'commits_behind' => $commitsBehind,
        'commit_messages' => $commitMessages
    ];
}

/**
 * Wykonuje aktualizację (git pull)
 */
function performUpdate(): array
{
    // Sprawdź czy nie ma lokalnych zmian
    exec('git status --porcelain 2>&1', $output, $returnCode);
    if (!empty($output)) {
        throw new Exception('Nie można zaktualizować - masz niezapisane zmiany lokalne. Zapisz lub odrzuć zmiany przed aktualizacją.');
    }

    // Pobierz aktualną gałąź
    exec('git rev-parse --abbrev-ref HEAD 2>&1', $output, $returnCode);
    $currentBranch = trim($output[0]);

    // Wykonaj git pull
    exec("git pull origin $currentBranch 2>&1", $output, $returnCode);

    if ($returnCode !== 0) {
        throw new Exception('Błąd podczas aktualizacji: ' . implode("\n", $output));
    }

    $pullOutput = implode("\n", $output);

    // Sprawdź czy były zmiany
    $updated = !str_contains($pullOutput, 'Already up to date');

    // Pobierz nowy commit hash
    exec('git rev-parse HEAD 2>&1', $output, $returnCode);
    $newCommit = substr(trim(end($output)), 0, 7);

    // Pobierz listę zmienionych plików
    $filesChanged = [];
    if ($updated) {
        exec("git diff --name-status HEAD@{1} HEAD 2>&1", $output, $returnCode);
        if ($returnCode === 0) {
            foreach ($output as $line) {
                $parts = preg_split('/\s+/', $line, 2);
                if (count($parts) === 2) {
                    $status = match($parts[0]) {
                        'A' => 'dodany',
                        'M' => 'zmodyfikowany',
                        'D' => 'usunięty',
                        default => $parts[0]
                    };
                    $filesChanged[] = [
                        'status' => $status,
                        'file' => $parts[1]
                    ];
                }
            }
        }
    }

    return [
        'updated' => $updated,
        'message' => $updated
            ? 'Projekt został zaktualizowany pomyślnie!'
            : 'Projekt jest już aktualny',
        'new_commit' => $newCommit,
        'files_changed' => $filesChanged
    ];
}

/**
 * Pobiera status git
 */
function getGitStatus(): array
{
    exec('git rev-parse --abbrev-ref HEAD 2>&1', $output, $returnCode);
    $branch = trim($output[0]);

    exec('git status --porcelain 2>&1', $output, $returnCode);
    $clean = empty($output);
    $uncommittedChanges = $output;

    return [
        'branch' => $branch,
        'clean' => $clean,
        'uncommitted_changes' => $uncommittedChanges
    ];
}
