<tr>
    <td>
        <select name="item[]" class="form-select select_item">
            <option value="" selected disabled>Select item...</option>
            <?php if (!empty($items)): ?>
                <?php foreach ($items as $item): ?>
                    <option value="<?= esc($item['item_id']) ?>">
                        <?= esc($item['item_name']) ?>
                    </option>
                <?php endforeach; ?>
            <?php else: ?>
                <option value="" disabled>No items found.</option>
            <?php endif; ?>
        </select>
    </td>

    <td>
        <div class="d-flex align-items-center justify-content-between gap-3">
            <input type="radio" name="status[<?= $rowIndex ?>]" value="OK" class="form-check-input">
            <label class="form-label text-success">OK</label>

            <input type="radio" name="status[<?= $rowIndex ?>]" value="NG" class="form-check-input">
            <label class="form-label text-danger">NG</label>
        </div>
    </td>

    <td>
        <select name="findings[]" class="form-select findings-dropdown">
            <option value="" selected disabled>Select findings...</option>
        </select>
    </td>

    <td>
        <input type="file" name="findingImg[]" accept=".png, .jpg, .jpeg" class="form-control">
    </td>

    <td>
        <textarea name="remarks[]" class="form-control"></textarea>
    </td>

    <td>
        <select name="jobReceiver[]" class="form-select">
            <option value="" selected disabled>Select job receiver...</option>
            <option value="ENGINEERING MODULE">ENGINEERING MODULE</option>
        </select>
    </td>

    <td>
        <input type="text" name="subControl[]" class="form-control" readonly>
    </td>
</tr>