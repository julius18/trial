<?php


add_filter( 'wpcf7_validate_text*', 'custom_validation_filter', 10, 2 );

function custom_validation_filter( $result, $tag ) { 
       $input_name = $tag['name'];
       $testt = $_POST['number-853'];
    //    echo $testt;
       if($input_name == 'number-853'){
            $string_length = strlen(str_replace(' ','', $_POST['number-853']));
            if($testt == 6){
                $result->invalidate($tag, 'testhjhh, reload page');
            }
            // echo '<pre>'; var_dump($string_length); die();
       }
                
       return $result; 
    }




// define the wpcf7_skip_mail callback 
function filter_wpcf7_skip_mail( $skip_mail, $contact_form ) { 
    $vaksin_name =$_POST['vaksin'];
    echo '<pre>'; var_dump($vaksin_name); die();

    return $skip_mail; 
}; 
         
// add the filter 
add_filter( 'wpcf7_skip_mail', 'filter_wpcf7_skip_mail', 10, 2 ); 







// // add the filter 
// add_filter( 'wpcf7_validate', 'filter_wpcf7_validate', 10, 2 );


    

//  // define the wpcf7_form_response_output callback 
//  function custom_wpcf7_form_response_output( $output, $class, $content, $instance, $status ){ 
//     //custom code here
   
//             echo 'testttt';
           

//     return $output;
// } 

// //add the action 
// add_filter('wpcf7_form_response_output', 'custom_wpcf7_form_response_output', 10, 5);
 

// // define the wpcf7_validate callback 
// function filter_wpcf7_validate( $result, $tag ) { 
//     $angka = 1;
    
//     if ($angka > 2){

//         echo 'testttt--1';
        
      


//         die();
//     } else {
//         $response = array(
//             "into" => "#something",
//             "status" => "validation_failed",
//             "message" => esc_attr( 'Sorry, some errors ocurred, please try again later.' ),
//         );
//         echo json_encode( $response );
//     }
    
//     return $result; 
// };