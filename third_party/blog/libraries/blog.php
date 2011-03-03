<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * MojoBlog
 *
 * A small, quick, and painfully simple 
 * blogging system for MojoMotor 
 *
 * @package 	mojoblog
 * @author 		Jamie Rumbelow <http://jamierumbelow.net>
 * @version		2.0.0
 * @copyright 	(c)2011 Jamie Rumbelow
 */

class Blog {
	
	/* --------------------------------------------------------------
	 * VARIABLES
	 * ------------------------------------------------------------ */
	
	private $mojo;
	private $data = array();
	private $version = '2.0.0';
	
	/* --------------------------------------------------------------
	 * GENERIC METHODS
	 * ------------------------------------------------------------ */
	
	public function __construct() {
		$this->mojo =& get_instance();
		
		$this->mojo->load->database();
		
		$this->mojo->load->model('member_model');
		$this->mojo->load->model('site_model');
		$this->mojo->load->model('blog_model');
		
		$this->mojo->load->library('auth');
		$this->mojo->load->library('javascript');
		$this->mojo->load->library('pagination');
		
		$this->mojo->load->helper('form');
		$this->mojo->load->helper('page');
		$this->mojo->load->helper('url');
	}
	
	/* --------------------------------------------------------------
	 * CONTROL PANEL
	 * ------------------------------------------------------------ */
	
	/**
	 * Display a list of entries in MojoBlog so that the user can 
	 * edit and create new posts
	 */
	public function index($offset = 0) {
		// Paginate like a mofo
		$config['base_url'] = site_url('admin/addons/blog/index');
		$config['total_rows'] = $this->mojo->blog_model->count_all_results();
		$config['uri_segment'] = 5;
		
		$this->mojo->pagination->initialize($config);
		
		// Load the entries from the DB
		$this->data['entries'] = $this->mojo->blog_model->limit($this->mojo->pagination->per_page, $offset)->get();
		
		// Load the view
		$this->_view('index');
	}
	
	/**
	 * Display the create entry form
	 */
	public function create() {
		// Setup some variables
		$this->data['validation'] = '';
		$this->data['entry'] = array('title' => '', 'content' => '', 'status' => '');
		$this->data['statuses'] = array('' => '---', 'published' => 'Published', 'draft' => 'Draft', 'review' => 'Review');
		
		// Handle entry submission
		if ($this->mojo->input->post('entry')) {
			// Get the entry data and set some stuff
			$this->data['entry']		 		= $this->mojo->input->post('entry');
			$this->data['entry']['author_id'] 	= $this->mojo->session->userdata('id');
			$this->data['entry']['date']		= date('Y-m-d H:i:s');
			$this->data['entry']['status']		= ($this->data['entry']['status']) ? $this->data['entry']['status'] : 'published';
			
			// Insert it!
			if ($this->mojo->blog_model->insert($this->data['entry'])) {
				// It's success
				$response['result'] = 'success';
				$response['reveal_page'] = site_url('admin/addons/blog/index');
				$response['message'] = 'Successfully created new entry';
				
				exit($this->mojo->javascript->generate_json($response));
			} else {
				// There have been validation errors
				$response['result'] = 'error';
				$response['message'] = $this->mojo->blog_model->validation_errors;
				
				// Output the response
				exit($this->mojo->javascript->generate_json($response));
			}
		}
		
		// Load that bitchin' view
		$this->_view('create');
	}
	
	/**
	 * Display the edit entry form
	 */
	public function edit($entry_id) {
		// Setup some variables
		$this->data['validation'] = '';
		$this->data['entry'] = $this->mojo->blog_model->where('id', $entry_id)->get(TRUE, TRUE);
		$this->data['statuses'] = array('' => '---', 'published' => 'Published', 'draft' => 'Draft', 'review' => 'Review');
		
		// Handle entry submission
		if ($this->mojo->input->post('entry')) {
			// Get the entry data and set some stuff
			$this->data['entry']		 		= $this->mojo->input->post('entry');
			$this->data['entry']['author_id'] 	= $this->mojo->session->userdata('id');
			$this->data['entry']['date']		= date('Y-m-d H:i:s');
			$this->data['entry']['status']		= ($this->data['entry']['status']) ? $this->data['entry']['status'] : 'published';
			
			// Insert it!
			if ($this->mojo->blog_model->where('id', $entry_id)->update($this->data['entry'])) {
				// It's success
				$response['result'] = 'success';
				$response['reveal_page'] = site_url('admin/addons/blog/index');
				$response['message'] = 'Successfully updated entry';
				
				exit($this->mojo->javascript->generate_json($response));
			} else {
				// There have been validation errors
				$response['result'] = 'error';
				$response['message'] = $this->mojo->blog_model->validation_errors;
				
				// Output the response
				exit($this->mojo->javascript->generate_json($response));
			}
		}
		
		// Load that bitchin' view
		$this->_view('edit');
	}
	
	/**
	 * Delete an entry
	 */
	public function delete($id) {
		// We've already confirmed by this point, so we
		// can go ahead and delete it
		$this->mojo->blog_model->where('id', $id)->delete();
		
		// Build the response
		$response['result'] = 'success';
		$response['message'] = 'Successfully deleted entry';
		$response['id'] = $id;
		
		// Output in JSON
		exit($this->mojo->javascript->generate_json($response));
	}
	
	/**
	 * Display a list of all the categories
	 */
	public function categories() {
		// Simple! Get the categories
		$this->data['categories'] = $this->mojo->blog_model->categories();
		
		// ...and display the view. We won't
		// be needing Pagination or anything like that
		$this->_view('categories');
	}
	
	/**
	 * Add a new category
	 */
	public function category_add() {
		// Setup validation
		$this->data['validation'] = "";
		$this->data['category']	= array('name' => '', 'url_name' => '');
		
		// Handle POST
		if ($this->mojo->input->post('category')) {
			// Get the category data
			$this->data['category']	= $this->mojo->input->post('category');
			
			// Insert it!
			if ($this->mojo->blog_model->insert_category($this->data['category'])) {
				// It's success
				$response['result'] = 'success';
				$response['reveal_page'] = site_url('admin/addons/blog/categories');
				$response['message'] = 'Successfully created category';
				
				exit($this->mojo->javascript->generate_json($response));
			} else {
				// There have been validation errors
				$response['result'] = 'error';
				$response['message'] = $this->mojo->blog_model->validation_errors;
				
				// Output the response
				exit($this->mojo->javascript->generate_json($response));
			}
		}
		
		// Show the view
		$this->_view('category_add');
	}
	
	/**
	 * Edit a category
	 */
	public function category_edit($id) {
		// Setup validation
		$this->data['validation'] = "";
		$this->data['category']	= $this->mojo->blog_model->where('id', $id)->category(TRUE);
		
		// Handle POST
		if ($this->mojo->input->post('category')) {
			// Get the category data
			$this->data['category']	= $this->mojo->input->post('category');
			
			// Insert it!
			if ($this->mojo->blog_model->where('id', $id)->update_category($this->data['category'])) {
				// It's success
				$response['result'] = 'success';
				$response['reveal_page'] = site_url('admin/addons/blog/categories');
				$response['message'] = 'Successfully updated category';
				
				exit($this->mojo->javascript->generate_json($response));
			} else {
				// There have been validation errors
				$response['result'] = 'error';
				$response['message'] = $this->mojo->blog_model->validation_errors;
				
				// Output the response
				exit($this->mojo->javascript->generate_json($response));
			}
		}
		
		// Show the view
		$this->_view('category_edit');
	}
	
	/**
	 * Display an image
	 */
	public function images($file) {
		header('Content-type: image/png');
		exit(file_get_contents(APPPATH . 'third_party/blog/images/' . $file));
	}
	
	/* --------------------------------------------------------------
	 * TEMPLATE TAGS
	 * ------------------------------------------------------------ */
	
	/**
	 * Initialises the MojoBlog system, loading the key scripts and
	 * stylesheets needed to run a copy of MojoBlog
	 */
	public function init() {
		if ($this->mojo->auth->is_editor()) {
			$html = 	'<script type="text/javascript" src="'.$this->javascript_url().'"></script>';
			$html .= 	'<script type="text/javascript">$(function(){ Mojo.URL.mojoblog_skin_url = "' 
								. base_url() . SYSDIR . '/mojomotor/third_party/blog/javascript/ckeditor/skins/kama/"; });</script>';
			$html .= 	'<link rel="stylesheet" type="text/css" href="'.$this->stylesheet_url().'" />';
		
			$this->mojo->cp->appended_output[] = $html;
		}
	}
	
	/**
	 * Loops through a blog's entries and displays them
	 *
	 * {mojo:blog:entries 
	 * 			page="about|home" global="yes" limit="10" entry_id="1" entry_id_segment="3" no_posts_404="yes" status="published"
	 *			orderby="date" sort="desc" date_format="Y-m-d" no_posts="No posts!" paginate="yes" per_page="5" pagination_trigger="p"}
	 *	   	{entries}
	 *     		<h1>{title}</h1>
	 *     		<p>{content}</p>
	 * 		{/entries}
	 *
	 * 		{pagination}{first_page_url} {prev_page_url} - Page {current_page} of {total_pages} - {next_page_url} {last_page_url}{/pagination}
	 * {/mojo:blog:entries}
	 *
	 * @todo Add {page_number_list} (Google style, 1 - 2 - 3 - *4* - 5)
	 */
	public function entries($template_data) {
		$this->template_data 	= $template_data;
		$page 					= $this->_param('page');
		$global 				= $this->_param('global');
		$limit 					= $this->_param('limit');
		$status 				= $this->_param('status');
		$entry_id 				= $this->_param('entry_id');
		$entry_id_segment 		= $this->_param('entry_id_segment');
		$no_posts_404	 		= $this->_param('no_posts_404');
		$orderby 				= $this->_param('orderby');
		$sort 					= $this->_param('sort');
		$date_format 			= $this->_param('date_format');
		$no_posts 				= $this->_param('no_posts');
		$paginate 				= $this->_param('paginate');
		$per_page 				= $this->_param('per_page');
		$pagination_trigger 	= $this->_param('pagination_trigger');
		
		// Limit access by page
		if (!$this->_limited_access_by_page($page)) {
			return '';
		}
		
		// Status
		if ($status) {
			// Get rid of 'not '
			$not = FALSE;
			if (substr($status, 0, 4) == 'not ') { $status = substr($status, 4); $not = TRUE; }
			
			// Multiple statuses
			$statuses = explode('|', $status);
			
			foreach ($statuses as $status) {
				if ($not) {
					$this->mojo->blog_model->where('status !=', $status);
				} else {
					$this->mojo->blog_model->or_where('status', $status);
				}
			}
		}
		
		// Orderby and sort
		$orderby = ($orderby) ? $orderby : 'date';
		$sort = ($sort) ? strtoupper($sort) : 'DESC';
		
		$this->mojo->blog_model->order_by("$orderby $sort");
		
		// Entry ID
		if ($entry_id) {
			$this->mojo->blog_model->where('id', $entry_id);
		}
		
		// Is there an entry ID in the URL?
		if ($entry_id_segment) {
			if ($this->mojo->uri->segment((int)$entry_id_segment)) {
				$this->mojo->blog_model->where('id', $this->mojo->uri->segment((int)$entry_id_segment));
				$paginate = FALSE;
			}
		}
		
		// Paginate?
		if ($paginate) {
			$per_page = ($per_page) ? $per_page : 5;
			$pagination_trigger = ($pagination_trigger) ? $pagination_trigger : 'p';
						
			if (isset($_REQUEST[$pagination_trigger])) {
				$page = (int)$_REQUEST[$pagination_trigger];
			} else {
				$page = 1;
			}
			
			// Work out the offset
			$offset = ($page-1) * $per_page;
			
			// Limit & offset!
			$this->mojo->blog_model->limit($per_page, $offset);
		} else {
			// Limit
			if ($limit) {
				$this->mojo->blog_model->limit($limit);
			}
		}
		
		// Get the posts
		$posts = $this->mojo->blog_model->get();
		$entries_tag = "";
		
		// Get a count for pagination
		if ($paginate) {
			$count = $this->mojo->blog_model->count_all_results();
		}
		
		// Any posts?
		if (!$posts) {
			if ($no_posts_404 == "yes") {
				show_404();
			} else {
				return ($no_posts) ? $no_posts : '';
			}
		} else {
			$parsed = "";
			
			// Do we have the {entries} tag at all?
			if (preg_match("/{entries}/", $this->template_data['template'])) {
				// Strip the template tags and replace with nothing
				$divs = '';
				$tags = array('{mojo:blog:entries}', '{/mojo:blog:entries}');
				$parsed = str_replace($tags, $divs, $this->template_data['template']);
				
				// Get the contents of the {entries}{/entries} tag
				preg_match("/\{entries\}(.*)\{\/entries\}/is", $this->template_data['template'], $internal_template);
				$internal_template = $internal_template[1];
								
				// Loop through and parse
				foreach ($posts as $post) {
					$tmp = $internal_template;
					$post->author = $this->mojo->db->where('id', $post->author_id)->get('members')->row()->email;
					
					// Start off with the basic variables
					$tmp = preg_replace("/{id}/i", $post->id, $tmp);
					$tmp = preg_replace("/{title}/i", $post->title, $tmp);
					$tmp = preg_replace("/{content}/i", $post->content, $tmp);
					$tmp = preg_replace("/{status}/i", ucwords($post->status), $tmp);
					$tmp = preg_replace("/{author}/i", $post->author, $tmp);
				
					// Then to the date!
					if ($date_format) {
						$tmp = preg_replace("/{date}/i", date($date_format, strtotime($post->date)), $tmp);
					} else {
						$tmp = preg_replace("/{date}/i", date('d/m/Y', strtotime($post->date)), $tmp);
					}
					
					// Finally, add it to the buffer
					$entries_tag .= $tmp;
				}
			}
			
			// Replace the entries with the entirety of the tag parsed
			$parsed = preg_replace("/\{entries\}(.*)\{\/entries\}/is", $entries_tag, $parsed);
			
			// Finish off with pagination
			if (preg_match("/\{pagination\}(.*)\{\/pagination\}/is", $parsed, $pagtmp)) {
				if ($paginate) {
					$first_page_url = site_url($this->mojo->mojomotor_parser->url_title);
					$prev_page_url = ($page > 1) ? site_url($this->mojo->mojomotor_parser->url_title.'?'.$pagination_trigger.'='.(string)($page-1)) : FALSE;
					$current_page = $page;
					$total_pages = round($count/$per_page);
					$next_page_url = ($page < ($count/$per_page)) ? site_url($this->mojo->mojomotor_parser->url_title.'?'.$pagination_trigger.'='.(string)($page+1)) : FALSE;
					$last_page_url = site_url($this->mojo->mojomotor_parser->url_title.'?'.$pagination_trigger.'='.(string)round($count/$per_page));
					$pagtmp = $pagtmp[1];

					// Prev and next page conditionals
					if ($prev_page_url) {
						if (preg_match("/\{if prev_page\}(.*?)\{\/if\}/is", $pagtmp)) {
							$pagtmp = preg_replace("/\{if prev_page\}(.*?)\{\/if\}/is", "$1", $pagtmp);
						}
					} else {
						if (preg_match("/\{if prev_page\}(.*?)\{\/if\}/is", $pagtmp)) {
							$pagtmp = preg_replace("/\{if prev_page\}(.*?)\{\/if\}/is", "", $pagtmp);
						}
					}

					if ($next_page_url) {
						if (preg_match("/\{if next_page\}(.*?)\{\/if\}/is", $pagtmp)) {
							$pagtmp = preg_replace("/\{if next_page\}(.*?)\{\/if\}/is", "$1", $pagtmp);
						}
					} else {
						if (preg_match("/\{if next_page\}(.*?)\{\/if\}/is", $pagtmp)) {
							$pagtmp = preg_replace("/\{if next_page\}(.*?)\{\/if\}/is", "", $pagtmp);
						}
					}

					// Variable swap fun
					$pagtmp = preg_replace("/\{first_page_url\}/i", $first_page_url, $pagtmp);
					$pagtmp = preg_replace("/\{prev_page_url\}/i", $prev_page_url, $pagtmp);
					$pagtmp = preg_replace("/\{current_page\}/i", $current_page, $pagtmp);
					$pagtmp = preg_replace("/\{total_pages\}/i", $total_pages, $pagtmp);
					$pagtmp = preg_replace("/\{next_page_url\}/i", $next_page_url, $pagtmp);
					$pagtmp = preg_replace("/\{last_page_url\}/i", $last_page_url, $pagtmp);
				
					// Replace {pagination} tags
					$parsed = preg_replace("/\{pagination\}(.*?)\{\/pagination\}/is", $pagtmp, $parsed);
				} else {
					$parsed = preg_replace("/\{pagination\}(.*)\{\/pagination\}/is", '', $parsed);
				}
			}
			
			// Return the parsed string!
			return $parsed;
		}
	}
	
	/**
	 * Outputs the URL to the RSS feed. Takes three parameters,
	 * the required 'blog' and an optional 'limit' and 'link_page'
	 *
	 * {mojo:blog:rss_url blog="blog" limit="15" link_page="about"}
	 */
	public function rss_url($template_data) {
		// Gather the variables
		$this->template_data = $template_data;
		$blog = $this->_param('blog');
		$limit = $this->_param('limit');
		$link_page = $this->_param('link_page');
		
		// Do we have a limit?
		$limit = ($limit) ? (int)$limit : 10;
		
		// Output the URL, basically
		return site_url("addons/blog/rss/$blog/$limit/$link_page");
	}
	
	
	/* --------------------------------------------------------------
	 * RSS/ATOM FEEDS
	 * ------------------------------------------------------------ */
	
	/**
	 * Display an RSS feed from the supplied blog,
	 * with an optional limit parameter (defaulting to 10)
	 */
	public function rss() {
		// Get the variables, brother
		$blog = $this->mojo->uri->segment(4);
		$limit = $this->mojo->uri->segment(5);
		$link_page = $this->mojo->uri->segment(6);
		
		// Make sure we've got a blog variable
		if (!$blog) {
			show_error("You're missing the blog name!");
		}
		
		// Get the posts, my friend
		$data['posts'] = $this->mojo->blog_model->where('blog', $blog)->order_by('date DESC')->limit($limit)->get();
		$data['site_name'] = $this->mojo->site_model->get_setting('site_name');
		$data['blog_name'] = $blog;
		$data['blog_pretty_name'] = ucwords(str_replace("_", " ", $blog));
		$data['rss_url'] = site_url('addons/blog/rss/'.$data['blog_name']);
		$data['link_page'] = ($link_page) ? $link_page : $this->mojo->site_model->default_page();
		
		// Set mime types and extract variables
		header("Content-type: text/xml");
		extract($data);
		
		// And output the RSS!
		include('blog/rss.xml');
	}
	
	/* --------------------------------------------------------------
	 * ASSETS (CSS & JAVASCRIPT)
	 * ------------------------------------------------------------ */
	
	/**
	 * Load the MojoBlog JavaScript
	 */
	public function javascript() {
		header("Content-Type: text/javascript");
		echo file_get_contents(APPPATH.'third_party/blog/javascript/mojoblog.js');
	}
	
	/**
	 * Load the MojoBlog CSS
	 */
	public function css() {
		header("Content-Type: text/css");
		echo file_get_contents(APPPATH.'third_party/blog/css/mojoblog.css');
	}
	
	/**
	 * Returns the correct URL to the MojoBlog JavaScript
	 */
	public function javascript_url() {
		return site_url('admin/addons/blog/javascript');
	}
	
	/**
	 * Returns the correct URL to the MojoBlog CSS
	 */
	public function stylesheet_url() {
		return site_url('admin/addons/blog/css');
	}
	
	/**
	 * Render an image from the addon
	 */
	public function image($file) {
		header('Content-Type: image/png');
		die(file_get_contents(APPPATH.'third_party/blog/images/'.$file));
	}
		
	/* --------------------------------------------------------------
	 * INSTALLATION ROUTINE
	 * ------------------------------------------------------------ */
	
	/**
	 * Installs MojoBlog
	 */
	public function install() {
		// Check that we're setup and the DB table exists
		$this->mojo->blog_model->install();
		
		// Make sure we update the routing
		$this->mojo->blog_model->install_routing();
		
		// Let the user know about it
		die('MojoBlog has been successfully installed!');
	}
	
	/**
	 * Uninstalls MojoBlog.
	 */
	public function uninstall() {
		// Are we allowed to do this?
		if (!$this->mojo->auth->is_admin()) {
			die('Unauthorised access!');
		}
		
		// Bye!
		$this->mojo->blog_model->uninstall();
		die('MojoBlog is uninstalled. Please remove the blog/ folder from mojomotor/third_party. If you do not do this you may receive errors.');
	}
	
	/**
	 * Update routine
	 */
	public function update() {
		$this->mojo->load->dbforge();
		
		// What's the current system version?
		$current = config_item('mojoblog_version');
		
		// Do we even have it?
		if (!$current) {
			// We know it's 1.1.3
			$current = '1.1.3';
		}
		
		// Do we need to upgrade at all?
		if ($this->version > $current) {
			// Run the upgrade
			$this->mojo->blog_model->upgrade($current, $this->version);
			
			// Write the current version
			$this->mojo->config->config_update(array('mojoblog_version' => $this->version));

			// Output a message
			die('You have upgraded MojoBlog to version ' . $this->version . '. Enjoy!');
		} else {
			// Output a message
			die('You are already running MojoBlog ' . $this->version . '. No upgrade needed!');
		}
	}
	
	/**
	 * Export everything for the EE importer
	 */
	public function export() {
		// Are we allowed to do this?
		if (!$this->mojo->auth->is_admin()) {
			die('Unauthorised access!');
		}
		
		// Get all the posts
		$blogs = $this->mojo->blog_model->select("DISTINCT(blog)")->get();
		$posts = array();
		
		foreach ($blogs as $blog) {
			$posts[$blog->blog] = $this->mojo->blog_model->where('blog', $blog->blog)->get();
		}
		
		// Build up the serialised PHP!
		$export_data['mojo_blog_export'] = TRUE;
		$export_data['blogs'] = $posts;
		$data = serialize($export_data);
		$filename = "mojoblog_export_".date('Y-m-d');
		
		header('Content-Type: application/php');
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header("Content-Transfer-Encoding: binary");
		header('Expires: 0');
		header('Pragma: no-cache');
		header("Content-Length: ".strlen($data));
		
		exit($data);
	}
	
	/* --------------------------------------------------------------
	 * HELPER METHODS
	 * ------------------------------------------------------------ */
	
	/**
	 * Load a view
	 */
	private function _view($view) {
		// ...save the original view path, and set to our Foo Bar package view folder
		$orig_view_path = $this->mojo->load->_ci_view_path;
		$this->mojo->load->_ci_view_path = APPPATH.'third_party/blog/views/';
		
		// ...load the view
		$this->mojo->load->view($view, $this->data);
		
		// ...then return the view path to the application's original view path
		$this->mojo->load->_ci_view_path = $orig_view_path;
	}
	
	/**
	 * Fetch a parameter
	 */
	private function _param($key) {
		return (isset($this->template_data['parameters'][$key])) ? $this->template_data['parameters'][$key] : FALSE;
	}
	
	/**
	 * Limit the access by page name, or bar separated list 
	 * of page names.
	 */
	private function _limited_access_by_page($page, $global = FALSE) {
		// Let's check the page variable, because
		// we don't want to show anything if we're
		// not on the right page.
		// 
		// ignore this if it's global.
		if ($global !== 'yes') {
			if ($page) {
				// Allow for bar|separated|pages
				if (strpos($page, '|')) {
					$pages = explode('|', $page);
				} else {
					$pages = array($page);
				}
				
				// Let's use a boolean to check for permissions
				$yo_brother_can_i_access_your_blog = FALSE;
				$default_page = $this->mojo->site_model->default_page();
				
				// Loop through the pages and check
				foreach ($pages as $possible_page) {
					$url = implode('/', $this->mojo->uri->rsegments);
					
					if ('page/content/' . $possible_page == $url || $possible_page == $default_page) {
						$yo_brother_can_i_access_your_blog = TRUE;
					}
				}
				
				// Are we on the right page? No? Well leave!
				if (!$yo_brother_can_i_access_your_blog) {
					return FALSE;
				}
			}
		}
		
		// I'm glad we got that over with
		return TRUE;
	}
}