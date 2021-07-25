<?php
// src/Controller/AppController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class AppController extends AbstractController
{
    public function generator_form(): Response
    {
        $html_string = file_get_contents('generator-form.html');
        return new Response($html_string);
    }

    /*
        TODO:
            - Clean up code / better comments
            - Handle generator parameters (post request)
            - Create config while with png paths and object IDs
            - 
            - Draw sun with limits/amount and more rules
            - Connect everything with middle routes/streets and use more street graphics
            - Add the rest of objects
    */

    /*
        KNOWN BUGS:
            - Sun happens to spawn too far out of the image
            - Barrier can spawn half on the middle street
            - Lights still can spawn in the same column
    */

    public function generator(): RedirectResponse
    {
        // Image dimensions
        $image_width = 4500;
        $image_height = 1500;

        // Tile size
        $tile_size = 100;
        // Amount of tiles for looping the tiles array
        $tiles_amount_x = $image_width / $tile_size;
        $tiles_amount_y = $image_height / $tile_size;
        $tiles_amount_total = $tiles_amount_x + $tiles_amount_y;

        // 2D tiles array filled with zeros ($tiles_amount_x * $tiles_amount_y)
        $tiles = array_fill(0, $tiles_amount_x, array_fill(0, $tiles_amount_y, 0));

        // Amount of things (defined by category, pass values with post request later)
        $amount_red_lights = 3;
        $amount_yellow_lights = 3;
        $amount_light_spots = $amount_red_lights + $amount_yellow_lights;
        $amount_suns = 1;
        $amount_barriers = 2;

        // Object IDs (unique integers represting an object/grahpic in the array)
        $vertical_street_object_id = 1001;
        $horizontal_street_object_id = 1002;
        // ...
        $light_spot_object_id = 1004;
        $yellow_light_object_id = 1005;
        $red_light_object_id = 1006;
        $sun_object_id = 1007;
        $barrier_object_id = 1008;

        // Path to the png files of the symbols
        $vertical_street_png_path = 'graphics/strasse-vertikal.png';
        $horizontal_street_png_path = 'graphics/strasse-horizontal.png';
        $light_spot_png_path = 'graphics/spot.png';
        $red_light_png_path = 'graphics/licht_rot.png';
        $yellow_light_png_path = 'graphics/licht_gelb.png';
        $sun_png_path = 'graphics/sonne.png';
        $barrier_png_path = 'graphics/barrikade.png';



        // Create new image object (main canvas)
        $img = @imagecreatetruecolor($image_width, $image_height)
            or die('Cannot Initialize new GD image stream');
        // Transparent background
        $color = imagecolorallocatealpha($img, 0, 0, 0, 127);
        imagefill($img, 0, 0, $color);
        imagesavealpha($img, TRUE);

        /*
            -------------------------------
                Middle Street
            -------------------------------
        */
        $middle_street_start_x = 1;
        $middle_street_end_x = $tiles_amount_x - 1;
        $middle_street_y = round($tiles_amount_y / 2);

        // Read original image size
        list($horizontal_street_width_org, $horizontal_street_height_org) = getimagesize($horizontal_street_png_path);
        // Load image into memory
        $horizontal_street_png = imagecreatefrompng($horizontal_street_png_path);

        for ($middle_street_x = $middle_street_start_x; $middle_street_x < $middle_street_end_x; $middle_street_x++) {
            $tiles[$middle_street_x][$middle_street_y] = $horizontal_street_object_id;
            imagecopyresampled(
                $img,
                $horizontal_street_png,
                $middle_street_x * $tile_size,
                $middle_street_y * $tile_size,
                0,
                0,
                $tile_size,
                $tile_size,
                $horizontal_street_width_org,
                $horizontal_street_height_org
            );
        }
        unset($horizontal_street_png);


        /*
            -------------------------------
                Light spots
            -------------------------------
        */
        // Read original image sizes
        list($light_spot_width_org, $light_spot_height_org) = getimagesize($light_spot_png_path);
        list($red_light_width_org, $red_light_height_org) = getimagesize($red_light_png_path);
        list($yellow_light_width_org, $yellow_light_height_org) = getimagesize($yellow_light_png_path);
        // Load image into memory
        $light_spot_png = imagecreatefrompng($light_spot_png_path);
        $red_light_png = imagecreatefrompng($red_light_png_path);
        $yellow_light_png = imagecreatefrompng($yellow_light_png_path);

        // Define area where lights should be placed (only in the middle)
        $placement_offset_left = 0.4;
        $placement_offset_right = 0.8;

        // Tempoary variables to count placed red/yellow lights
        $placed_red_lights = 0;
        $placed_yellow_lights = 0;

        // Blocked columns, already occupied
        $blocked_light_columns = [];

        // Place Red/Yellow Lights and Lightspots
        for ($i = 0; $i < $amount_light_spots; $i++) {
            // Generate random position and check if its not occupied
            $coordinate_x = rand(
                $placement_offset_left * $tiles_amount_x,
                $placement_offset_right * $tiles_amount_x - 1
            );
            $coordinate_y = rand(0, $tiles_amount_y - 1);

            $allowed_to_place = true;

            // check if allowed to spawn light in column
            if (in_array($coordinate_x, $blocked_light_columns)) {
                $allowed_to_place = false;
            }

            while ($tiles[$coordinate_x][$coordinate_y] !== 0 && $allowed_to_place) {
                $coordinate_x = rand(
                    $placement_offset_left * $tiles_amount_x,
                    $placement_offset_right * $tiles_amount_x - 1
                );
                $coordinate_y = rand(0, $tiles_amount_y - 1);

                $allowed_to_place = true;
                if (in_array($coordinate_x, $blocked_light_columns)) {
                    $allowed_to_place = false;
                }
            }

            // Draw lightspot
            imagecopyresampled(
                $img,
                $light_spot_png,
                $coordinate_x * $tile_size,
                $coordinate_y * $tile_size,
                0,
                0,
                $tile_size,
                $tile_size,
                $light_spot_width_org,
                $light_spot_height_org
            );

            // block cloumn
            array_push($blocked_light_columns, $coordinate_x);

            // Decide random wheather to place red or yellow light
            $light_to_place = 1; // Place yellow light by default
            if ($placed_red_lights < $amount_red_lights) { // Allowed to place red lights?
                if ($placed_yellow_lights < $amount_yellow_lights) { // Allowed to place yellow lights?
                    $light_to_place = rand(0, 1); // Choose one random
                } else {
                    $light_to_place = 0; // Place red light
                }
            }

            if ($light_to_place === 1) {
                $placed_yellow_lights++;
                $tiles[$coordinate_x][$coordinate_y] = $yellow_light_object_id;
                imagecopyresampled(
                    $img,
                    $yellow_light_png,
                    $coordinate_x * $tile_size,
                    $coordinate_y * $tile_size,
                    0,
                    0,
                    $tile_size,
                    $tile_size,
                    $red_light_width_org,
                    $red_light_height_org
                );
            } else {
                $amount_red_lights++;
                $tiles[$coordinate_x][$coordinate_y] = $red_light_object_id;
                imagecopyresampled(
                    $img,
                    $red_light_png,
                    $coordinate_x * $tile_size,
                    $coordinate_y * $tile_size,
                    0,
                    0,
                    $tile_size,
                    $tile_size,
                    $red_light_width_org,
                    $red_light_height_org
                );
            }
        }
        // free up memory / unset images
        unset($red_light_png);
        unset($yellow_light_png);
        unset($light_spot_png);

        /*
            -------------------------------
                Barriers
            -------------------------------
        */
        // Calculate barrier size
        list($barrier_width_org, $barrier_height_org) = getimagesize($barrier_png_path);
        // Load image into memory
        $barrier_png = imagecreatefrompng($barrier_png_path);

        $barrier_placement_offset_left = 0.3;
        $barrier_placement_offset_right = 0.7;

        $placed_barriers = 0;

        for ($i = 0; $placed_barriers < $amount_barriers; $i++) {
            $barrier_coordinate_x = rand(
                $barrier_placement_offset_left * $tiles_amount_x,
                $barrier_placement_offset_right * $tiles_amount_x - 1
            );
            $barrier_coordinate_y = rand(0, $tiles_amount_y - 2);
            // Check if barrier has 1 empty tile below
            while (
                ($tiles[$barrier_coordinate_x][$barrier_coordinate_y] !== 0 &&
                    $tiles[$barrier_coordinate_x][$barrier_coordinate_y + 1] !== 0)
            ) {
                $barrier_coordinate_x = rand(
                    $barrier_placement_offset_left * $tiles_amount_x,
                    $barrier_placement_offset_right * $tiles_amount_x - 1
                );
                $barrier_coordinate_y = rand(0, $tiles_amount_y - 2);
            }

            $placed_barriers++;
            $tiles[$barrier_coordinate_x][$barrier_coordinate_y] = $barrier_object_id;
            $tiles[$barrier_coordinate_x][$barrier_coordinate_y + 1] = $barrier_object_id;
            imagecopyresampled(
                $img,
                $barrier_png,
                $barrier_coordinate_x * $tile_size,
                $barrier_coordinate_y * $tile_size,
                0,
                0,
                $tile_size,
                $tile_size * 2,
                $barrier_width_org,
                $barrier_height_org
            );
        }
        unset($barrier_png);


        /*
            -------------------------------
                Sun
            -------------------------------
        */
        // Calculate sun size
        list($sun_width_org, $sun_height_org) = getimagesize($sun_png_path);
        // Load image into memory
        $sun_png = imagecreatefrompng($sun_png_path);

        $sun_placement_offset_left = 0.8;
        $sun_placement_offset_right = 1;

        $sun_coordinate_x = rand(
            $sun_placement_offset_left * $tiles_amount_x,
            $sun_placement_offset_right * $tiles_amount_x - 2
        );
        $sun_coordinate_y = rand(0, $tiles_amount_y - 1);
        // Check if sun has 4 empty tiles
        while (
            ($tiles[$sun_coordinate_x][$sun_coordinate_y] !== 0 &&
                $tiles[$sun_coordinate_x + 1][$sun_coordinate_y] !== 0 &&
                $tiles[$sun_coordinate_x][$sun_coordinate_y + 1] !== 0 &&
                $tiles[$sun_coordinate_x + 1][$sun_coordinate_y + 1] !== 0)
        ) {
            $sun_coordinate_x = rand(
                $sun_placement_offset_left * $tiles_amount_x,
                $sun_placement_offset_right * $tiles_amount_x - 2
            );
            $sun_coordinate_y = rand(0, $tiles_amount_y - 1);
        }

        $tiles[$sun_coordinate_x][$sun_coordinate_y] = $sun_object_id;
        $tiles[$sun_coordinate_x + 1][$sun_coordinate_y] = $sun_object_id;
        $tiles[$sun_coordinate_x][$sun_coordinate_y + 1] = $sun_object_id;
        $tiles[$sun_coordinate_x + 1][$sun_coordinate_y + 1] = $sun_object_id;

        imagecopyresampled(
            $img,
            $sun_png,
            $sun_coordinate_x * $tile_size,
            $sun_coordinate_y * $tile_size,
            0,
            0,
            $tile_size * 2,
            $tile_size * 2,
            $sun_width_org,
            $sun_height_org
        );

        unset($sun_png);


        /*
            -------------------------------
                Save graphic etc.
            -------------------------------
        */
        imagepng($img, 'generated/elementary-001.png');

        // Free up memory
        imagedestroy($img);

        // Redirect to generated image
        return $this->redirect('generated/elementary-001.png');
    }
}
