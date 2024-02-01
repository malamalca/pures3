<?php
    use App\Core\App;
    use Michelf\MarkdownExtra;
?>
<p class="actions">
    <a class="button" href="<?= App::url('/hrup/projekti/view/' . $projectId) ?>">&larr; Nazaj</a>
</p>
<?php
    echo MarkdownExtra::defaultTransform($porocilo);
?>