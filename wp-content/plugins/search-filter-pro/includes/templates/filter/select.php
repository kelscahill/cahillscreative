<?php if ( $data['showLabel'] === 'yes' ) { ?><div class="search-filter-label" id="search-filter-label-<?php echo $data['labelUid']; ?>"><?php echo $data['label']; ?></div><?php } ?><div class="search-filter-input-combobox search-filter-input-combobox--mode-<?php if ( $data['multiple'] ) { ?>multiple<?php }else { ?>single<?php } ?> search-filter-input-combobox--search-enabled search-filter-input-combobox--scale-<?php echo $data['scale']; ?> search-filter-input-combobox--listbox-position-auto" aria-haspopup="listbox" aria-expanded="false" aria-controls="search-filter-input-combobox-0__listbox" role="combobox" aria-labelledby="search-filter-label-<?php echo $data['labelUid']; ?>" tabindex="-1"><div class="search-filter-input-combobox__header"><div class="search-filter-input-combobox__actions search-filter-input-combobox__actions--empty"><?php if ( $data['multiple'] ) { ?><div class="search-filter-input-combobox__selection"><?php foreach ( $data['selection'] as $data_1 ) { ?><div class="search-filter-input-combobox__selection-item"><div class="search-filter-input-combobox__selection-label"><?php echo $data_1['label']; ?></div><div class="search-filter-input-combobox__selection-remove"><svg><use href="#sf-svg-clear"></use></svg></div></div><?php } ?><input type="text" autocapitalize="off" autocomplete="off" spellcheck="true" aria-labelledby="search-filter-label-<?php echo $data['labelUid']; ?>" aria-controls="search-filter-input-combobox-0__listbox" id="search-filter-input-combobox-0__text_input" aria-autocomplete="list" class="search-filter-input-combobox__actions_input" placeholder="<?php echo $data['placeholderText']; ?>"></div><?php } ?><?php if ( ! $data['multiple'] ) { ?><div class="search-filter-input-combobox__selection"><span><?php echo $data['selectionLabel']; ?></span></div><input type="text" autocapitalize="off" autocomplete="off" spellcheck="true" aria-labelledby="search-filter-label-<?php echo $data['labelUid']; ?>" aria-controls="search-filter-input-combobox-0__listbox" id="search-filter-input-combobox-0__text_input" aria-autocomplete="list" class="search-filter-input-combobox__actions_input" placeholder="<?php echo $data['placeholderText']; ?>"><?php } ?></div><div class="search-filter-input-combobox__clear-selection search-filter-input-combobox--hidden"><svg><use href="#sf-svg-clear"></use></svg></div><div class="search-filter-input-combobox__listbox-toggle"><svg><use href="#sf-svg-arrow-down"></use></svg></div></div><div aria-live="polite" role="status" class="search-filter-input-combobox__screen-reader-text">2 results available.</div></div>