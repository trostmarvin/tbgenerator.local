<?php
// src/Controller/AppController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use App\Entity\UploadedImage;

class AppController extends AbstractController
{
    public function generator_form(): Response
    {
        $html_string = file_get_contents('generator_files/app.html');
        return new Response($html_string);
    }

    public function generator(Request $request): Response
    {
        // TODO: add function here to clear old uploads...

        $category = $request->get('category');
        $contest = $request->get('contest');
        $image = $request->files->get('image');

        if($image) {
            // let vich uploader handle the file
            $uploaded_image = new UploadedImage();
            $uploaded_image->setImageFile($image);

            // write changes
            $em = $this->getDoctrine()->getManager();
            $em->persist($uploaded_image);
            $em->flush();

            // Return generator result with input image
            return $this->generate(
                $category,
                $contest,
                $uploaded_image->getImageName()
            );
        }

        return $this->generate(
            $category,
            $contest,
            false
        );
    }

    private function generate($category, $contest, $image_file_name) {

        /* Generator Variables */

        $image_path = 'generator_uploads/'; 
        $template_path = 'generator_files/templates/';
        $graphics_path = 'generator_files/graphics/';
        $rendered_images_path = 'generator_files/rendered/';
        $config_path = 'generator_files/configs/';
        $config_file_name = $category.'_'.$contest.'.json';

        /* Generator settings */
        // try to open generator settings file

        try {
            $generator_settings = json_decode(file_get_contents($config_path . $config_file_name), true);
        } catch (\Exception $e) {
            return ($this->json([
                "success" => false,
                "error" => "Internal Error: failed to open config file... no category or contest of that type?"
            ]));
        }

        $objects = $generator_settings["objects"];


        /* Decide phase based on if an image is given or not */
        if(!$image_file_name) {
            // Load template file and meta data
            $img = imagecreatefrompng($template_path . $generator_settings["template_file_name"]);
            list($img_width, $img_height) = getimagesize($template_path . $generator_settings["template_file_name"]);
            $phase = "phase_1";
        } else {
            // Load input image and meta data
            $img = imagecreatefrompng($image_path . $image_file_name);
            list($img_width, $img_height) = getimagesize($image_path . $image_file_name);
            $phase = "phase_2";
        }

        $objects_to_place = $generator_settings[$phase]["objects_to_place"];

        /* Do stuff based on phase */
        if($phase === "phase1") {

        } else if ($phase === "phase2") {

        }

        /* Place objects graphics on the image */
        // TODO: implement error handling

        $blocked_coordinates = array();
        for($i = sizeof($objects_to_place) -1; $i >= 0; $i--) {
            
            $object_name = $objects_to_place[$i][0];
            $object_phase_config = $objects_to_place[$i][1];

            // Place X amount of object type
            for($j = $object_phase_config["amount"]; $j > 0; $j--) {
                // Read object config
                $object_global_config = $generator_settings["objects"][$object_name];

                // Open object graphic file and get its size
                list($object_width, $object_height) = getimagesize( $graphics_path . $object_global_config["object_file_name"] );
                // Load image into memory
                $object_png = imagecreatefrompng( $graphics_path . $object_global_config["object_file_name"] );

                // Choose random possible coordinate from the "possible_coordinates" array in the global object config,
                // and check if its blocked. Won't work even if the coordinate differ by only 1...
                do {
                    $selected_coodinate_array_index = array_rand( $object_global_config["possible_coordinates"] );
                    $selected_coordinates = $object_global_config["possible_coordinates"][$selected_coodinate_array_index];
                } while (array_search($selected_coordinates, $blocked_coordinates) !== false);

                // Add selected coordinates to blocked coordinates
                array_push($blocked_coordinates, $selected_coordinates);

                // Place objects png at selected coordinates
                imagecopyresampled(
                    $img,
                    $object_png,
                    $selected_coordinates[0],
                    $selected_coordinates[1],
                    0,
                    0,
                    $object_width,
                    $object_height,
                    $object_width,
                    $object_height
                );

                // Free image from memory
                unset($object_png);
            } 
        }

        /* Save image temporarily */
        imagepng($img, $rendered_images_path . 'rendered_image.png');

        // Free up memory
        imagedestroy($img);

        /* JSON Response */
        return($this->json([
            "success" => true,
            "image_file" => $image_path . $image_file_name,
            "config_used" => $generator_settings
        ]));
    }
}
