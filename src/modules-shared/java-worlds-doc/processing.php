<?
    function javawd_source_with_download_link(
        string $java_worlds_repo_path,
        string $variant,
        string $program,
        bool $include_download_link = true,
        ?string $highlight_lines = null,
    ) {
?>
<? source_file($java_worlds_repo_path . '/variants/' . $variant . '/programs/' . $program . '/java_src/Steuereinheit.java', highlight_lines: $highlight_lines); ?>
<?
        if ($include_download_link) {
?>
            <p>
                <em>Projekt herunterladen:</em> <a href="<?= to_url($java_worlds_repo_path . '/variants/' . $variant . '/' . $program . '.zip') ?>"><code class="language-none"><?= $program ?>.zip</code></a>
            </p>
<?
        }
    }
?>