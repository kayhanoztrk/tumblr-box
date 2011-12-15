<?php
 /*
Plugin Name: Tumblr Box
Plugin URI: http://www.kayhanozturk.org
Description: Display your tumblr posts on wordpress
Author: Kayhan Öztürk
Version: 1.1
Author URI: http://www.kayhanozturk.org
*/

     class Tumblr_Box extends WP_Widget {

      function Tumblr_Box() {
		$widget_ops = array( 'classname' => 'Tumblr', 'description' => 'Displays tumblr posts on wp.' );
		$control_ops = array( 'width' => 300, 'height' => 350, 'id_base' => 'tumblr-box' );
		$this->WP_Widget( 'tumblr-box', 'Tumblr Box', $widget_ops, $control_ops );
	}

function widget( $args, $options ) {

	if (!function_exists('simplexml_load_string')) {
		
			echo "SimpleXML can not found.";
			exit;
	
	}

	function link_tumblr($post_url, $time) {
		echo '<p><a href="'.$post_url.'" class="tumblr_link">'.date('m/d/y', intval($time)).'</a></p>';
	}

	$tumblrcache = get_option('tumblrcache');

	/* Set up variables and arguments */	
	extract( $args );
	$title = apply_filters('widget_title', $options['title'] );
	$tumblr = $options['tumblr'];
	$tumblr = rtrim($tumblr, "/ \t\n\r");
	$photo_size = $options['photo_size'];
	$show_photo = $options['show_photo'];
	$show_quote = $options['show_quote'];
	$show_link = $options['show_link'];
	$show_audio = $options['show_audio'];
	$number = $options['number'];
	

	$types = array (
		"photo" => $show_photo,
		"quote" => $show_quote,
		"link" => $show_link,
		"audio" => $show_audio,
		);
	
	
	if ( $tumblrcache['lastcheck'] <  ( mktime() - 60 ) ) {	
		$count = 0;
		foreach( $types as $type ) {
			if ($type)
				$count++;
			}
		//clean URL
		if ( strpos($tumblr, "http://") === 0 )
			$tumblr = substr($tumblr, 7);
		$tumblr = rtrim($tumblr, "/");
	
		// if selected only one category.
		if ( $count == 1 ) {
			foreach ( $types as $type => $value ) {
				if ( $value )
					$the_type = $type;
				}
			$request_url = "http://".$tumblr."/api/read?num=".$number."&type=".$the_type;
			}
	
	
	//If selected more category
		else {
			$request_url = "http://".$tumblr."/api/read?num=".$number;
			}
		
		//making request
		$request = new WP_Http;
		$result = $request->request( $request_url );
		
		if ( is_wp_error($result) ) {
			echo "Error: " . $result->get_error_message();
			return;
		}
		
		
		if ( strpos($result['body'], "<!DOCT") !== 0 ) {		
			$tumblrcache['xml'] = $result['body'];
			$tumblrcache['lastcheck'] = mktime();
			update_option('tumblrcache', $tumblrcache);
		}
	} // end if

	/* Using the cached version, whether or not it was just updated. */
	$xml_string = $tumblrcache['xml'];
	try {	
		$xml = simplexml_load_string($xml_string);
	} catch (Exception $e) {
		//Ignore the error and insure $xml is null
		echo 'Something wrong';	
		$xml == null;
	}
	
	if ( !empty($xml) ) {
	
		echo $before_widget;
		if ( $title ) {
			echo $before_title;
			if ( $link_title ) {
				echo "<a href='http://" . $tumblr . "'>" . $title . "</a>";
			} else {
				echo $title;
			}
			echo $after_title;
		}
		echo '<ul>';
		$post_count = 0;

		
		foreach ( $xml->posts->post as $post ) {

			if ( $post_count < $number ) {
			
				foreach ($post->attributes() as $key => $value) {
					if ( $key == "type" )
						$type = $value;
					if ( $key == "unix-timestamp" )
						$time = $value;
					if ( $key == "url" )
						$post_url = $value;
				}


					

// PHOTO POSTS
					if ( $type == "photo" && $show_photo ) {
						echo '<li class="tumblr_post '.$type.'" ';
						if ($inline_styles) {
							echo 'style="padding:8px 0"';
						}
						echo '>';
						$caption = $post->{'photo-caption'};
						foreach ($post->{'photo-url'} as $this_url) {
							foreach ($this_url->attributes() as $key => $value) {
								if ($value == $photo_size) {
									$url = $this_url;
									}
								if ($value == 500) {
									$link_url = $this_url;
									}
								}
							}
						echo '<a href="'.$link_url.'"><img src="'.$url.'" alt="photo from Tumblr" /></a><br />'.$caption.'<br />';
						if ($show_time) {
							link_tumblr($post_url, $time);
						}
						echo '</li>';
						$post_count++;
					}

// QUOTE POSTS
					if ($type == "quote" && $show_quote) {
						echo '<li class="tumblr_post '.$type.'" ';
						if ($inline_styles) {
							echo 'style="padding:8px 0"';
						}
						echo '>';
						$text = $post->{'quote-text'};
						$source = $post->{'quote-source'};
						echo '<p><blockquote>'.$text.'</blockquote>'.$source.'</p>';
						if ($show_time) {
							link_tumblr($post_url, $time);
						}
						echo '</li>';
						$post_count++;
					}

// LINK POSTS
					if ($type == "link" && $show_link) {
						echo '<li class="tumblr_post '.$type.'" ';
						if ($inline_styles) {
							echo 'style="padding:8px 0"';
						}
						echo '>';
						$text = $post->{'link-text'};
						$url = $post->{'link-url'};
						$description = $post->{'link-description'};
						echo '<p><a href="'.$url.'">'.$text.'</a> '.$description.'</p>';
						if ($show_time) {
							link_tumblr($post_url, $time);
						}
						echo '</li>';
						$post_count++;
					}


					

// AUDIO POSTS
					if ($type == "audio" && $show_video) {
						echo '<li class="tumblr_post '.$type.'" ';
						if ($inline_styles) {
							echo 'style="padding:8px 0"';
						}
						echo '>';
						$caption = $post->{'audio-caption'};
						$player = $post->{'audio-player'};
						echo $player."<br />".$caption."<br />";
						if ($show_time) {
							link_tumblr($post_url, $time);
						}
						echo '</li>';
						$post_count++;
					}
				} // end of loop
			} // $post_count == number;
		// end of widget
		echo '</ul>'.$after_widget;
		} else {
			
				echo '<span class="error">Sorry, loading error.</span>';
			
		}
	}





function update( $new_options, $old_options ) {
		$options = $old_options;
		
		if ($new_options['tumblr'] != $options['tumblr']) {
			delete_option('tumblrcache');
		}

		$options['title'] = strip_tags( $new_options['title'] );
		$options['tumblr'] = strip_tags( $new_options['tumblr'] );
		$options['photo_size'] = $new_options['photo_size'];
		$options['show_photo'] =$new_options['show_photo'];
		$options['show_quote'] = $new_options['show_quote'];
		$options['show_link'] = $new_options['show_link'];
		$options['show_audio'] = $new_options['show_audio'];
		$options['number'] = $new_options['number'];

		return $options;
	}

// creates settings form
function form( $options ) {

// default settings
		$default_settings = array( 'title'=>'My Tumblr', 'tumblr'=>'boskefm.tumblr.com','show_photo' => true, 'show_quote' => true, 'show_link' => true, 'show_audio'=>true,'number'=>10 );
		$options = wp_parse_args( (array) $options, $default_settings ); ?>

<?php // form html ?>
			<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>">Box Title:</label>
			<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $options['title']; ?>" style="width:100%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'tumblr' ); ?>">Your Tumblr:</label>
			<input id="<?php echo $this->get_field_id( 'tumblr' ); ?>" name="<?php echo $this->get_field_name( 'tumblr' ); ?>" value="<?php echo $options['tumblr']; ?>" style="width:100%;" />
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'number' ); ?>">Select number of posts to display:</label>

			<select id="<?php echo $this->get_field_id( 'number' ); ?>" name="<?php echo $this->get_field_name( 'number' ); ?>" value="<?php echo $options['number']; ?>">

			<option value="1" <?php if ($options['number']==1) echo 'selected="selected"'; ?>>1</option>

			<option value="2" <?php if ($options['number']==2) echo 'selected="selected"'; ?>>2</option>

			<option value="3" <?php if ($options['number']==3) echo 'selected="selected"'; ?>>3</option>

			<option value="5" <?php if ($options['number']==5) echo 'selected="selected"'; ?>>5</option>

			<option value="10" <?php if ($options['number']==10) echo 'selected="selected"'; ?>>10</option>

			<option value="15" <?php if ($options['number']==15) echo 'selected="selected"'; ?>>15</option>

			<option value="20" <?php if ($options['number']==20) echo 'selected="selected"'; ?>>20</option>

			<option value="25" <?php if ($options['number']==25) echo 'selected="selected"'; ?>>25</option>
			</select>
		</p>

			

		



<hr />

		<p><strong>Show:</strong></p>

		

		<p>
			<input class="checkbox" type="checkbox" <?php if ($options['show_photo']) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'show_photo' ); ?>" name="<?php echo $this->get_field_name( 'show_photo' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_photo' ); ?>">Photo posts</label>
		</p>

		<p>
			<label for="<?php echo $this->get_field_id( 'photo_size' ); ?>">Photo size:</label>
			<select id="<?php echo $this->get_field_id( 'photo_size' ); ?>" name="<?php echo $this->get_field_name( 'photo_size' ); ?>" value="<?php echo $options['photo_size']; ?>"><option value="75" <?php if ($instance['photo_size']==75) echo 'selected="selected"'; ?>>75px</option><option value="100" <?php if ($instance['photo_size']==100) echo 'selected="selected"'; ?>>100px</option><option value="250" <?php if ($instance['photo_size']==250) echo 'selected="selected"'; ?>>250px</option><option value="400" <?php if ($instance['photo_size']==400) echo 'selected="selected"'; ?>>400px</option><option value="500" <?php if ($instance['photo_size']==500) echo 'selected="selected"'; ?>>500px</option></select>
		</p>

		<p>
			<input class="checkbox" type="checkbox" <?php if ($options['show_quote']) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'show_quote' ); ?>" name="<?php echo $this->get_field_name( 'show_quote' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_quote' ); ?>">Quotation posts</label>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php if ($options['show_link']) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'show_link' ); ?>" name="<?php echo $this->get_field_name( 'show_link' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_link' ); ?>">Link posts</label>
		</p>
	

		<p>
			<input class="checkbox" type="checkbox" <?php if ($options['show_audio']) echo 'checked'; ?> id="<?php echo $this->get_field_id( 'show_audio' ); ?>" name="<?php echo $this->get_field_name( 'show_audio' ); ?>" />
			<label for="<?php echo $this->get_field_id( 'show_audio' ); ?>">Audio posts</label>
		</p>

	

			<?php
	}
}
add_action( 'widgets_init', 'load_tumblr_box' );
function load_tumblr_box() {
	register_widget( 'Tumblr_Box' );
}
?>