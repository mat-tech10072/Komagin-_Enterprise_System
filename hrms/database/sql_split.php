<?php
/**
 * Phase 3 — Correct SQL statement splitter.
 *
 * Both the old and (initially) the rewritten install.php split SQL files
 * into statements with a naive explode(';', $sql) — which breaks the
 * instant a semicolon appears anywhere it isn't a statement terminator:
 * inside a quoted string (document templates' HTML bodies are full of
 * inline style="color:red;margin:4px;" attributes) or inside an
 * explanatory `-- comment` sentence using normal punctuation. This
 * function is a minimal, correct tokenizer for exactly this purpose: it
 * tracks single-quote/double-quote string state and `--`/`#` line-comment
 * state, and only treats `;` as a delimiter when neither applies.
 *
 * Used by database/install.php and database/verify_clean_install.php so
 * there is exactly one statement-splitting implementation, not two that
 * can drift.
 */
function splitSqlStatements(string $sql): array {
    $statements = [];
    $current = '';
    $len = strlen($sql);
    $inString = false;
    $stringChar = '';
    $inLineComment = false;
    $i = 0;

    while ($i < $len) {
        $ch = $sql[$i];

        if ($inLineComment) {
            $current .= $ch;
            if ($ch === "\n") { $inLineComment = false; }
            $i++;
            continue;
        }

        if ($inString) {
            $current .= $ch;
            if ($ch === '\\' && $i + 1 < $len) {
                // escaped character — consume it literally, don't let it close the string
                $current .= $sql[$i + 1];
                $i += 2;
                continue;
            }
            if ($ch === $stringChar) { $inString = false; }
            $i++;
            continue;
        }

        // Not in a string or comment — check for comment start
        if ($ch === '-' && $i + 1 < $len && $sql[$i + 1] === '-') {
            $inLineComment = true;
            $current .= $ch;
            $i++;
            continue;
        }
        if ($ch === '#') {
            $inLineComment = true;
            $current .= $ch;
            $i++;
            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $inString = true;
            $stringChar = $ch;
            $current .= $ch;
            $i++;
            continue;
        }
        if ($ch === ';') {
            $statements[] = $current;
            $current = '';
            $i++;
            continue;
        }

        $current .= $ch;
        $i++;
    }
    if (trim($current) !== '') $statements[] = $current;

    // Drop statements that are comment-only or blank once assembled.
    return array_values(array_filter(array_map('trim', $statements), function ($s) {
        if ($s === '') return false;
        foreach (explode("\n", $s) as $line) {
            $t = trim($line);
            if ($t === '') continue;
            if (str_starts_with($t, '--')) continue;
            if (str_starts_with($t, '#')) continue;
            return true; // found a real, non-comment, non-blank line
        }
        return false;
    }));
}
