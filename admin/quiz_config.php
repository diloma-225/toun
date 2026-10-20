<?php
function quizDetect(PDO $con)
{
    $table = '';
    foreach (['t_quiz', 't_quizzes'] as $t) {
        try {
            $s = $con->prepare('SHOW TABLES LIKE ?');
            $s->execute([$t]);
            if ($s->fetchColumn()) {
                $table = $t;
                break;
            }
        } catch (Throwable $e) {
        }
    }
    if (!$table) {
        return null;
    }
    $cols = [];
    try {
        $s = $con->query("SHOW COLUMNS FROM `$table`");
        $cols = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
    } catch (Throwable $e) {
    }
    if (!$cols) {
        return null;
    }
    $names = array_column($cols, 'Field');
    $find = function ($list) use ($names) {
        foreach ($list as $x) {
            if (in_array($x, $names, true)) {
                return $x;
            }
        }
        return null;
    };
    $id = $find(['id_quiz', 'id_quizzes', 'id']);
    if (!$id) {
        $id = $names[0];
    }
    return [
        'table' => $table,
        'cols' => $cols,
        'names' => $names,
        'id' => $id,
        'title' => $find(['titre_quiz', 'nom_quiz', 'libelle_quiz', 'titre', 'nom']),
        'desc' => $find(['descrip_quiz', 'description_quiz', 'description']),
        'form' => $find(['id_form', 'formation_id']),
        'status' => $find(['statut_quiz', 'statut']),
        'duration' => $find(['duree_quiz', 'duree']),
        'score' => $find(['score_quiz', 'note_quiz', 'points'])
    ];
}
function quizValue(array $row, $key)
{
    return $key && array_key_exists($key, $row) ? $row[$key] : '';
}
function quizLabel($name)
{
    $x = preg_replace('/^id_/', '', str_replace('_', ' ', (string) $name));
    return ucwords($x);
}
function quizAuto(array $col)
{
    return stripos($col['Extra'] ?? '', 'auto_increment') !== false ||
        in_array(strtolower($col['Field']), ['created_at', 'updated_at', 'date_creation', 'date_modification'], true);
}