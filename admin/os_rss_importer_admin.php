<div class="wrap">
	<h2><?php _e('Configuración', 'os_rss_importer_plugin'); ?></h2>

	<form method="post" action="options.php">
		<?php settings_fields('os-rss-importer-form'); ?>

		<table>
			<tr>
				<th><?php _e('Frecuencia de actualización','os-rss-importer-plugin'); ?></th>
				<td>

					<?php
						$frecuencias = array(
							array('hourly','Cada hora'),
							array('twicedaily','Dos veces al día'),
							array('daily','Cada día'),
							);

					?>
					<select name="frecuencia">
					<?php

						foreach($frecuencias as &$frecuencia){
							echo '<option value="'.$frecuencia[0].'"';
							if($frecuencia[0] == get_option('frecuencia'))
								echo 'selected ';
							echo '>';
							echo _e($frecuencia[1],'os-rss-importer-plugin').'</option>';
						}
					?>
					</select>
				</td>
			</tr>

			<tr>
				<th><?php _e('URL del RSS','os-rss-importer-plugin') ?></th>
				<td style="width: 100%;"><input style="width: 100%;" required placeholder="http://" type="text" name="url_rss" value="<?php echo get_option('url_rss'); ?>"/></td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>
</div>