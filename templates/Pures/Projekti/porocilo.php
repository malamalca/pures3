<?php
    use App\Core\App;
    use Michelf\MarkdownExtra;
?>
<p class="actions">
    <a class="button" href="<?= App::url('/pures/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>
<div class="porocilo">
<?php
    echo MarkdownExtra::defaultTransform($porocilo);
?>
</div>