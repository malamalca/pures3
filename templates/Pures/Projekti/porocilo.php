<?php
    use App\Core\App;
?>
<p class="actions">
<a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>

<?php
    use Michelf\MarkdownExtra;
    echo MarkdownExtra::defaultTransform($porocilo);