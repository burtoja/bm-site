<?php
// search_form_template.php

?>
<div class="search-form">
    <label for="condition">Condition:</label>
    <select name="condition">
        <option value="">All</option>
        <option value="new" <?= ($condition === "new") ? "selected" : "" ?>>New</option>
        <option value="used" <?= ($condition === "used") ? "selected" : "" ?>>Used</option>
    </select>

    <label for="manufacturer">Manufacturer:</label>
    <input type="text" name="manufacturer" value="<?= htmlspecialchars($manufacturer) ?>" />

    <button type="submit">Search</button>
</div>
