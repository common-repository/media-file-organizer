<table class="panel" id="<?php echo $id ?>">
	<thead>


	</thead>
	<tbody>

    <tr id="panel_actions">

        <td>
            <div id="panel_dir">
                <input type="text" autocomplete="off" class="dir-text" value="<?php echo ! empty( $list[ 'dir' ] ) ? '/'.$list[ 'dir' ] : '/' ?>" >
            </div>

           <!-- <input type="checkbox" class="select-all">--> <div class="select-all"> Select All </div> <div class="deselect-all"> Deselect All </div><div class="up"></div> <div class="new"></div>

        </td>
    </tr>

	<tr>
		<td class="panel-list"><?php Media_File_Organizer_View::show('list', ['list'=> $list ]) ?></td>
	</tr>
	</tbody>
</table>
