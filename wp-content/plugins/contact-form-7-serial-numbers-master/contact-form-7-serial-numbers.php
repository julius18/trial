<?php
/*
Plugin Name: Contact Form 7 Serial Numbers
Version: 0.8.1
Description: The just another serial numbering plugin for cantact for contact form 7.
Author: Kiminori KATO
Author URI: http://www.29lab.jp/
Text Domain: contact-form-7-serial-numbers
Domain Path: /languages
*/

define( 'NKLAB_WPCF7SN_PLUGIN_DIR', untrailingslashit( dirname( __FILE__ ) ) );
require_once NKLAB_WPCF7SN_PLUGIN_DIR . '/includes/class-contact_list_table.php';


class ContactForm7_Serial_Numbers {

    private $options;
    private $is_active_cf7ac;
    const OPTION_SAVE_FILE = 'wpcf7sn_options.txt';
    const DOMAIN = 'contact-form-7-serial-numbers';

    function __construct() {
        $this->options = $this->get_plugin_options();

        // プラグインが有効化された時に実行されるメソッドを登録
        if ( function_exists( 'register_activation_hook' ) )
            register_activation_hook( __FILE__, array( &$this, 'activation' ) );
        // プラグインが停止されたときに実行されるメソッドを登録
        if ( function_exists( 'register_deactivation_hook' ) )
            register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ) );

        // アクションフックの設定
        add_action( 'admin_init', array( &$this, 'admin_init' ) );
        add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
        add_action( 'wpcf7_before_send_mail', array( &$this, 'increment_count' ) );

        // フィルターフックの設定
        add_filter( 'wpcf7_special_mail_tags', array( &$this, 'special_mail_tags' ), 10, 2 );
        add_filter( 'wpcf7_posted_data',       array( &$this, 'add_serial_number_to_posted_data' ), 10, 1 );

        // ショートコードの設定
        add_shortcode( 'wpcf7sn_view_count', array( &$this, 'view_serial_number' ) );

        // 言語ファイルの読み込み
        load_plugin_textdomain( self::DOMAIN, false, basename( dirname( __FILE__ ) ) . '/languages' );

        // Contact Form 7 add confirm が有効化されているか
        // 有効化している場合は 1, していない場合は 0
        $this->is_active_cf7ac =
            ( $this->is_active_plugin('contact-form-7-add-confirm/contact-form-7-confirm.php') ) ?
            intval( $this->is_active_plugin('contact-form-7-add-confirm/contact-form-7-confirm.php') ) : 0;
    }

    // plugin activation
    function activation() {
        $option_file = dirname( __FILE__ ) . '/' . self::OPTION_SAVE_FILE;
        if ( file_exists( $option_file ) ) {
            $wk_options = unserialize( file_get_contents( $option_file ) );
            if ( $wk_options != $this->options ) {
                $this->options = $wk_options;
                foreach ( $this->options as $key=>$value ) {
                    update_option( $key, $value );
                }
                unlink( $option_file );
            }
        }
    }

    // plugin deactivation
    function deactivation() {
        $option_file = dirname( __FILE__ ) . '/' . self::OPTION_SAVE_FILE;
        $wk_options = serialize( $this->options );
        if ( file_put_contents( $option_file, $wk_options ) && file_exists( $option_file ) ) {
            foreach( $this->options as $key=>$value ) {
                delete_option( $key );
            }
        }
    }

    // get plugin options
    function get_plugin_options() {
        global $wpdb;
        $values = array();
        $results = $wpdb->get_results( "
            SELECT *
              FROM $wpdb->options
             WHERE 1 = 1
               AND option_name like 'nklab_wpcf7sn_%'
             ORDER BY option_name
        " );

        foreach ( $results as $result ) {
            $values[ $result->option_name ] = $result->option_value;
        }

        return $values;
    }

    // admin init
    function admin_init() {
        wp_enqueue_style( 'contact-form-7-serial-numbers', plugin_dir_url( __FILE__ ) . 'css/style.css' );
    }

    // admin menu
    function admin_menu() {
        add_options_page(
            __( 'Contact Form 7 Serial Numbers', self::DOMAIN ),
            __( 'Contact Form 7 Serial Numbers', self::DOMAIN ),
            'level_8',
            __FILE__,
            array( &$this, 'wpcf7sn_admin_opt_page' )
        );
    }

    // option page
    function wpcf7sn_admin_opt_page() {
        $list_table = new NKLAB_WPCF7SN_Contact_List_Table();
        $list_table->prepare_items();

        if ( is_admin() ) {
            // jQuery 利用
            wp_enqueue_script( 'jquery' );
            wp_enqueue_script( self::DOMAIN, plugin_dir_url( __FILE__ ) . 'js/contact-form-7-serial-numbers.js' );
        }
?>
<div class="wrap">
    <h2><?php _e( 'Contact Form 7 Serial Numbers', self::DOMAIN ); ?></h2>
    <p></p>
    <p><?php _e( 'Copy the code of mail tags and paste it into any location ( ex. message body or subject etc.) of mail templates of Contact Form 7.', self::DOMAIN ); ?></p>

    <?php $list_table->display(); ?>
</div>
<?php
    }

    // increment count
    function increment_count( $contactform ) {
        // allow count up flag
        $allow_count_up = false;

        // get form id
        $id = intval( $contactform->id() );

        // get count
        $count = ( get_option( 'nklab_wpcf7sn_count_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_count_' . $id ) ) : 0;

        // check count up
        if ( $this->is_active_cf7ac == 1 ) {
            if ( isset( $_POST['_wpcf7c'] ) && ( "step1" != $_POST['_wpcf7c'] ) ) {
                // Contact Form 7 add confirm を有効化しているが、 _wpcf7c に step1 が入っていない
                $allow_count_up = true;
            }
        } else {
            // Contact Form 7 add confirm を有効化していない
            $allow_count_up = true;
        }

        if ( $allow_count_up ) {
            update_option( 'nklab_wpcf7sn_count_' . $id, intval( $count + 1 ) );
        }
    }

    // is active plugin
    function is_active_plugin( $plugin ) {
        if ( function_exists('is_plugin_active') ) {
            return is_plugin_active( $plugin );
        } else {
            return in_array( $plugin, get_option('active_plugins') );
        }
    }

    // special mail tags
    function special_mail_tags( $output, $name ) {
        if ( ! isset( $_POST['_wpcf7_unit_tag'] ) || empty( $_POST['_wpcf7_unit_tag'] ) ) return $output;
        $name = preg_replace( '/^wpcf7\./', '_', $name );

        if ( 'cf7_serial_number_' == substr( $name, 0, 18 ) ) {
            // form id の取得
            $id = intval( substr( $name, 18 ) );

            // 通し番号設定の取得
            $digits = ( get_option( 'nklab_wpcf7sn_digits_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_digits_' . $id ) ) : 0;
            $type   = ( get_option( 'nklab_wpcf7sn_type_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_type_' . $id ) ) : 1;
            $prefix = ( get_option( 'nklab_wpcf7sn_prefix_' . $id ) ) ? get_option( 'nklab_wpcf7sn_prefix_' . $id ) : '';
            $count  = ( get_option( 'nklab_wpcf7sn_count_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_count_' . $id ) ) : 0;

            switch( $type ) {
                case 1:
                    // 番号
                    $output = $count;
                    if ( $digits ) {
                        $output = sprintf( "%0" . $digits . "d", $output );
                    }
                    break;
                case 2:
                    // タイムスタンプ
                    $output = microtime( true ) * 10000;
                    break;
                default:
                    $output = '';
            }
            $output = $prefix . $output;

            // 通し番号設定値のSession、またはCookieに一時的に記録
            if ( isset( $_SESSION ) ) {
                // セッションが有効
                $_SESSION[ 'wpcf7sn_output_' . $id ] = $output;
            } else {
                // セッションが無効なため、有効期限 1 分の Cookie を利用
                setcookie( 'wpcf7sn_output_' . $id, $output, time() + 60 );
            }
        }
        return $output;
    }

    // add serial number to posted data
    function add_serial_number_to_posted_data( $posted_data ) {

        if ( !empty( $posted_data ) ) {
            // id の取得
            $id = intval( $posted_data['_wpcf7'] );

            // 通し番号設定の取得
            $digits = ( get_option( 'nklab_wpcf7sn_digits_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_digits_' . $id ) ) : 0;
            $type   = ( get_option( 'nklab_wpcf7sn_type_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_type_' . $id ) ) : 1;
            $prefix = ( get_option( 'nklab_wpcf7sn_prefix_' . $id ) ) ? get_option( 'nklab_wpcf7sn_prefix_' . $id ) : '';
            $count  = ( get_option( 'nklab_wpcf7sn_count_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_count_' . $id ) ) : 0;

            switch( $type ) {
                case 1:
                    // 番号
                    $output = $count + 1;
                    if ( $digits ) {
                        $output = sprintf( "%0" . $digits . "d", $output );
                    }
                    break;
                case 2:
                    // タイムスタンプ
                    $output = microtime( true ) * 10000;
                    break;
                default:
                    $output = '';
            }
            $output = $prefix . $output;

            $posted_data['Serial Number'] = $output;
        }

        return $posted_data;
    }

    // ShortCode
    function view_serial_number( $atts ) {
        // 引数の取得
        extract(shortcode_atts(array(
            'id' => 0,
        ), $atts));

        if ( isset( $_SESSION[ 'wpcf7sn_output_' . $id ] ) ) {
            // セッションが有効
            $output = $_SESSION[ 'wpcf7sn_output_' . $id ];
        } else {
            // セッションが無効なため、Cookie で
            if ( isset( $_COOKIE[ 'wpcf7sn_output_' . $id ] ) ) {
                $output = $_COOKIE[ 'wpcf7sn_output_' . $id ];
            } else {
                // セッションもCookieもダメ
                $digits = ( get_option( 'nklab_wpcf7sn_digits_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_digits_' . $id ) ) : 0;
                $type   = ( get_option( 'nklab_wpcf7sn_type_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_type_' . $id ) ) : 1;
                $prefix = ( get_option( 'nklab_wpcf7sn_prefix_' . $id ) ) ? get_option( 'nklab_wpcf7sn_prefix_' . $id ) : '';
                $count  = ( get_option( 'nklab_wpcf7sn_count_' . $id ) ) ? intval( get_option( 'nklab_wpcf7sn_count_' . $id ) ) : 0;

                switch( $type ) {
                    case 1:
                        // 番号
                        $output = $count;
                        if ( $digits ) {
                            $output = sprintf( "%0" . $digits . "d", $output );
                        }
                        break;
                    case 2:
                        // タイムスタンプ（メール生成時のタイムスタンプとは異なる）
                        $output = microtime( true ) * 10000;
                        break;
                    default:
                        $output = '';
                }
                $output = $prefix . $output;
            }
        }

        return $output;
    }

}

$NKLAB_WPCF7_SerialNumbers = new ContactForm7_Serial_Numbers();

?>
