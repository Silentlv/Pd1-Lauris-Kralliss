<?php
// task_fields.php - uzdevuma formas lauki.
// Izmanto gan add.php, gan edit.php, lai nebutu jaraksta divreiz.
// Sagaida mainigos $uzd (uzdevuma dati), $kategorijas un $statusi.
?>
<input type="hidden" name="token" value="<?= $_SESSION['token'] ?>">

<label>Nosaukums</label>
<input type="text" name="title" value="<?= h($uzd['title']) ?>" required maxlength="100">

<label>Apraksts</label>
<textarea name="description" maxlength="1000"><?= h($uzd['description']) ?></textarea>

<label>Termiņš</label>
<input type="date" name="due_date" value="<?= h($uzd['due_date']) ?>">

<label>Statuss</label>
<select name="status">
    <?php foreach ($statusi as $s) { ?>
        <!-- selected atzime pasreizejo statusu -->
        <option <?= $uzd['status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php } ?>
</select>

<label>Kategorija</label>
<select name="category_id">
    <option value="">-</option>
    <?php foreach ($kategorijas as $k) { ?>
        <option value="<?= $k['id'] ?>" <?= $uzd['category_id'] == $k['id'] ? 'selected' : '' ?>><?= h($k['name']) ?></option>
    <?php } ?>
</select>
