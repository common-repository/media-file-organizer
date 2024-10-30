
<div class="scrollable">
    <table>
        <tr> <td id="dir" data-value="<?php echo $list[ 'dir' ] ?>"></td></tr>
		<?php

		$folders = isset( $list[ 'folders'] ) ? $list[ 'folders' ] : [] ;
		$files = isset( $list[ 'files' ] ) ? $list[ 'files' ] : [] ;
		$i = 1 ; // Checkbox counter
		foreach ( $folders as $folder ){
			?>
            <tr class="list-item">
                <td class="folder" data-name="<?php echo $folder[ 'name' ]?>">
                    <input type="checkbox" class="selector chk-<?php echo $i ?>" data-index="<?php echo $i ?>" data-name="<?php echo $folder[ 'name' ]?>" >
                    <img align="center" src="<?php echo MEDIA_FILE_ORGANIZER_PLUGIN_URL.'/images/orig/dir.png' ?>">
					<?php echo $folder[ 'name' ] ?>
                </td>
            </tr>
			<?php

            $i ++ ;
		}

		foreach ( $files as $file ){
			?>
            <tr class="list-item">
                <td class="file <?php echo $file[ 'type' ]?>" data-name="<?php echo $file[ 'name' ]?>">
                    <input type="checkbox" class="selector chk-<?php echo $i ?>" data-index="<?php echo $i ?>"  data-name="<?php echo $file[ 'name' ]?>">
                    <img  align="center" src="<?php echo $file[ 'thumb' ]?>">
					<?php echo $file[ 'name' ] ?>
                </td>
            </tr>
			<?php

            $i ++ ;
		}
		?>
    </table>
</div>
