<?php
    use App\Core\App;
?>
<p>
    <h1>Podatki JSON:</h1>
    <form method="post">
        <input type="text" name="GKY" placeholder="GKY" value="<?= $GKY ?>" />
        <input type="text" name="GKX" placeholder="GKX" value="<?= $GKX ?>"  />
        <textarea name="data" style="font-family: monospace; resize: vertical; width: 100%; min-height: 200px;"><?= h($jsonKons ?? '') ?></textarea>
        <button type="submit">Pošlji</button>
    </form>
</p>
<script type="text/javascript">
    var textareas = document.getElementsByTagName('textarea');
    var count = textareas.length;
    for(var i=0;i<count;i++){
        textareas[i].onkeydown = function(e){
            if(e.keyCode==9 || e.which==9){
                e.preventDefault();
                var s = this.selectionStart;
                this.value = this.value.substring(0,this.selectionStart) + "    " + this.value.substring(this.selectionEnd);
                this.selectionEnd = s+4; 
            }
        }
    }
</script>
<?php
    if (!empty($kons)) {
        echo $this->element('Pures' . DS . 'Konstrukcije' . DS . 'view');
    }
?>