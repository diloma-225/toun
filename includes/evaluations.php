<?php

function tounQuizAType(PDO $con): bool
{
    try {
        $colonnes = $con->query('SHOW COLUMNS FROM t_quiz')->fetchAll(PDO::FETCH_COLUMN, 0);
    } catch (Throwable $e) {
        return false;
    }

    return in_array('type_quiz', $colonnes, true);
}

function tounQuizApprenant(PDO $con, int $idQuiz, int $idUser): ?array
{
    $stmt = $con->prepare("
        SELECT
            q.id_quiz,
            q.titre_quiz,
            q.description_quiz,
            q.note_minimale,
            q.id_module,
            " . (tounQuizAType($con) ? 'q.type_quiz' : "'final'") . " AS type_quiz,
            m.titre_module,
            f.id_form,
            f.titre_form
        FROM t_quiz q
        INNER JOIN t_modules m ON m.id_module = q.id_module
        INNER JOIN t_formations f ON f.id_form = m.id_form
        INNER JOIN t_inscriptions i ON i.id_form = f.id_form
        WHERE q.id_quiz = ?
          AND i.id_user = ?
          AND i.statut_inscrip IN ('active', 'terminee')
        LIMIT 1
    ");

    $stmt->execute([$idQuiz, $idUser]);

    $quiz = $stmt->fetch(PDO::FETCH_ASSOC);

    return $quiz ?: null;
}

function tounQuizQuestions(PDO $con, int $idQuiz): array
{
    $stmt = $con->prepare("
        SELECT id_question, libelle_question, ordre_question
        FROM t_questions
        WHERE id_quiz = ?
        ORDER BY ordre_question ASC, id_question ASC
    ");

    $stmt->execute([$idQuiz]);

    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$questions) {
        return [];
    }

    $ids = array_column($questions, 'id_question');
    $marques = implode(',', array_fill(0, count($ids), '?'));

    $stmtReponses = $con->prepare("
        SELECT id_reponse, id_question, libelle_reponse, est_correcte
        FROM t_reponses
        WHERE id_question IN ($marques)
        ORDER BY id_reponse ASC
    ");

    $stmtReponses->execute($ids);

    $reponsesParQuestion = [];

    foreach ($stmtReponses->fetchAll(PDO::FETCH_ASSOC) as $reponse) {
        $reponsesParQuestion[(int) $reponse['id_question']][] = $reponse;
    }

    foreach ($questions as &$question) {
        $question['reponses'] = $reponsesParQuestion[(int) $question['id_question']] ?? [];
    }

    unset($question);

    return array_values(array_filter($questions, fn ($question) => count($question['reponses']) >= 2));
}

function tounNoteMinimale(array $quiz): float
{
    $note = (float) ($quiz['note_minimale'] ?? 10);

    if ($note > 20) {
        $note = $note / 5;
    }

    return round(max(0, min(20, $note)), 2);
}

function tounCorrigerQuiz(array $questions, array $choix): array
{
    $bonnes = 0;
    $corrections = [];

    foreach ($questions as $question) {
        $idQuestion = (int) $question['id_question'];
        $idChoisi = (int) ($choix[$idQuestion] ?? 0);
        $bonneReponse = null;
        $reponseChoisie = null;

        foreach ($question['reponses'] as $reponse) {
            if ((int) $reponse['est_correcte'] === 1 && $bonneReponse === null) {
                $bonneReponse = $reponse;
            }

            if ((int) $reponse['id_reponse'] === $idChoisi) {
                $reponseChoisie = $reponse;
            }
        }

        $estJuste = $reponseChoisie !== null && (int) $reponseChoisie['est_correcte'] === 1;

        if ($estJuste) {
            $bonnes++;
        }

        $corrections[] = [
            'question' => $question['libelle_question'],
            'choisie' => $reponseChoisie['libelle_reponse'] ?? '',
            'bonne' => $bonneReponse['libelle_reponse'] ?? '',
            'juste' => $estJuste,
        ];
    }

    $total = count($questions);

    return [
        'bonnes' => $bonnes,
        'total' => $total,
        'note' => $total > 0 ? round(($bonnes / $total) * 20, 2) : 0,
        'corrections' => $corrections,
    ];
}

function tounEnregistrerResultat(PDO $con, int $idUser, int $idQuiz, float $note, bool $reussi): void
{
    $stmt = $con->prepare("
        INSERT INTO t_resultats_quiz (id_user, id_quiz, note_obtenue, statut_resultat, date_passage)
        VALUES (?, ?, ?, ?, NOW())
    ");

    $stmt->execute([$idUser, $idQuiz, $note, $reussi ? 'reussi' : 'echoue']);
}

function tounDernierResultat(PDO $con, int $idUser, int $idQuiz): ?array
{
    $stmt = $con->prepare("
        SELECT id_resultat, note_obtenue, statut_resultat, date_passage
        FROM t_resultats_quiz
        WHERE id_user = ? AND id_quiz = ?
        ORDER BY id_resultat DESC
        LIMIT 1
    ");

    $stmt->execute([$idUser, $idQuiz]);

    $resultat = $stmt->fetch(PDO::FETCH_ASSOC);

    return $resultat ?: null;
}

function tounNombreTentatives(PDO $con, int $idUser, int $idQuiz): int
{
    $stmt = $con->prepare("SELECT COUNT(*) FROM t_resultats_quiz WHERE id_user = ? AND id_quiz = ?");
    $stmt->execute([$idUser, $idQuiz]);

    return (int) $stmt->fetchColumn();
}

function tounFormatNote(float $note): string
{
    return rtrim(rtrim(number_format($note, 2, ',', ''), '0'), ',');
}

function tounQuizDeFormation(PDO $con, int $idUser, int $idForm): array
{
    $stmt = $con->prepare("
        SELECT
            q.id_quiz,
            q.titre_quiz,
            q.id_module,
            " . (tounQuizAType($con) ? 'q.type_quiz' : "'final'") . " AS type_quiz,
            EXISTS (
                SELECT 1
                FROM t_resultats_quiz r
                WHERE r.id_quiz = q.id_quiz
                  AND r.id_user = ?
                  AND r.statut_resultat = 'reussi'
            ) AS reussi
        FROM t_quiz q
        INNER JOIN t_modules m ON m.id_module = q.id_module
        WHERE m.id_form = ?
        ORDER BY m.ordre_module ASC, q.id_quiz ASC
    ");

    $stmt->execute([$idUser, $idForm]);

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function tounParcours(PDO $con, int $idUser, int $idForm): array
{
    $stmt = $con->prepare('SELECT id_module, titre_module FROM t_modules WHERE id_form = ? ORDER BY ordre_module ASC, id_module ASC');
    $stmt->execute([$idForm]);
    $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $con->prepare("
        SELECT
            l.id_module,
            COUNT(*) AS total,
            SUM(EXISTS (
                SELECT 1
                FROM t_suivi_contenus s
                WHERE s.id_user = ? AND s.type_element = 'lecon' AND s.id_element = l.id_lecon
            )) AS faites
        FROM t_lecons l
        INNER JOIN t_modules m ON m.id_module = l.id_module
        WHERE m.id_form = ?
        GROUP BY l.id_module
    ");
    $stmt->execute([$idUser, $idForm]);
    $lecons = [];

    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $ligne) {
        $lecons[(int) $ligne['id_module']] = $ligne;
    }

    $stmt = $con->prepare("SELECT id_element FROM t_suivi_contenus WHERE id_user = ? AND type_element = 'module'");
    $stmt->execute([$idUser]);
    $modulesFaits = array_flip(array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN)));

    $quiz = tounQuizDeFormation($con, $idUser, $idForm);
    $modules = [];
    $precedentValide = true;
    $coursTermines = (bool) $lignes;
    $testsReussis = true;

    foreach ($lignes as $ligne) {
        $idModule = (int) $ligne['id_module'];
        $total = (int) ($lecons[$idModule]['total'] ?? 0);
        $faites = (int) ($lecons[$idModule]['faites'] ?? 0);
        $termine = $total > 0 ? $faites >= $total : isset($modulesFaits[$idModule]);
        $ouvert = $precedentValide;
        $test = null;

        foreach ($quiz as $element) {
            if ($element['type_quiz'] === 'module' && (int) $element['id_module'] === $idModule) {
                $test = [
                    'id_quiz' => (int) $element['id_quiz'],
                    'titre_quiz' => $element['titre_quiz'],
                    'reussi' => (bool) (int) $element['reussi'],
                    'disponible' => $ouvert && $termine,
                ];
                break;
            }
        }

        $modules[$idModule] = [
            'id_module' => $idModule,
            'titre_module' => $ligne['titre_module'],
            'ouvert' => $ouvert,
            'termine' => $termine,
            'test' => $test,
        ];

        if (!$termine) {
            $coursTermines = false;
        }

        if ($test && !$test['reussi']) {
            $testsReussis = false;
        }

        $precedentValide = $ouvert && (!$test || $test['reussi']);
    }

    $finaux = [];

    foreach ($quiz as $element) {
        if ($element['type_quiz'] !== 'module') {
            $finaux[] = [
                'id_quiz' => (int) $element['id_quiz'],
                'titre_quiz' => $element['titre_quiz'],
                'reussi' => (bool) (int) $element['reussi'],
                'disponible' => $coursTermines && $testsReussis,
            ];
        }
    }

    return [
        'modules' => $modules,
        'finaux' => $finaux,
        'cours_termines' => $coursTermines,
        'tests_reussis' => $testsReussis,
    ];
}

function tounQuizOuvert(array $parcours, int $idQuiz): bool
{
    foreach ($parcours['modules'] as $module) {
        if ($module['test'] && $module['test']['id_quiz'] === $idQuiz) {
            return $module['test']['disponible'];
        }
    }

    foreach ($parcours['finaux'] as $quiz) {
        if ($quiz['id_quiz'] === $idQuiz) {
            return $quiz['disponible'];
        }
    }

    return false;
}

function tounModuleOuvert(array $parcours, int $idModule): bool
{
    return $parcours['modules'][$idModule]['ouvert'] ?? false;
}

function tounModuleSuivant(array $parcours, int $idModule): ?array
{
    $trouve = false;

    foreach ($parcours['modules'] as $module) {
        if ($trouve) {
            return $module;
        }

        if ($module['id_module'] === $idModule) {
            $trouve = true;
        }
    }

    return null;
}
