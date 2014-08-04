<?php namespace Radiantweb\Flousps\Classes;

use Radiantweb\Flocommerce\Models\Cart as FlocommerceCart;
use Radiantweb\Flocommerce\Models\Product as FlocommerceProduct;
use Validator;
use October\Rain\Support\ValidationException;
use Session;
use View;
/**
 * Suply shipping form & cost
 * Requires min php 5.3  
 *
 * @package radiantweb/flo
 * @author ChadStrat
 */
class Usps
{
    public function __construct($type=null)
    {

    }

    /*
     * @form inputs 
     * returns any number of input types needed for this shipping form
     * these inputs will be passed back to the calculateShipping method via $params
     * ZIP code is not needed and is passed via $params['shipping_zip']
     * 'shipping_service' should be used as the input name for shipping type sub-name:
     * ex - USPS(shipping_type),PRIORITY MAIL(shipping_service)
     * this is used in the order line item shipping description
     * ################################################################################
     * !!! All inputs must have data-rel="shipping_form_vars" set to be passed !!!
     * ################################################################################
     */
    public static function getShippingForm()
    {
        return View::make('radiantweb.flousps::shipping_form');
    }
    
    /*
     * @params 
     * - $params = form inputs defined above
     * - $session = cartID
     * - $items = cart items via $session
     * calculates shipping cost and returns the total shipping amount
     */
    public static function calculateShipping($params)
    {
        /* 
         * SETTINGS *******
         * @from_zip - zip code you are shipping from
         * @api_key - your shipping supplier API key
         * ****************
         */
        $from_zip = '44106';
        $api_key = '947RADIA7871';

        /* XML client */
        $client = 'http://production.shippingapis.com/ShippingAPI.dll?API=RateV4&XML=';

        /* begin XML request build */
        $requestXML = '<RateV4Request USERID="'.$api_key.'"><Revision/>';

        /* get cart by session */
        $session = Session::get('flo_cart');

        $cart = new FlocommerceCart();
        $items = $cart->getCartItems($session);

        if(is_array($items)){
            /* loop through each line item of the cart */
            foreach($items as $line_item){
                $tempXML = '';
                //get teh product
                $product = FlocommerceProduct::where('id', '=', $line_item->product_id)->first();
                //store to a temp xml so we can multiply
                $tempXML .= '<Package ID="'.$line_item->id.'">';
                $tempXML .= '<Service>'.$params['form_vars']['shipping_service'].'</Service>';
                $tempXML .= '<FirstClassMailType>PARCEL</FirstClassMailType>';
                $tempXML .= '<ZipOrigination>'.$from_zip.'</ZipOrigination>';
                $tempXML .= '<ZipDestination>'.$params['shipping_zip'].'</ZipDestination>';
                $tempXML .= '<Pounds>'.$product->shipping_weight.'</Pounds>';
                $tempXML .= '<Ounces>0</Ounces>';
                $tempXML .= '<Container>VARIABLE</Container>';
                $tempXML .= '<Size>REGULAR</Size>';
                $tempXML .= '<Width>'.$product->shipping_width.'</Width>';
                $tempXML .= '<Length>'.$product->shipping_height.'</Length>';
                $tempXML .= '<Height>'.$product->shipping_depth.'</Height>';
                $tempXML .= '</Package>';

                /* repeat-add product shipping info for each of qty */
                for($i=0;$i<$line_item->qty;$i++){
                    $requestXML .= $tempXML;
                }
            }
        }
        $requestXML .= '</RateV4Request>';

        /* get XML return */
        $response = file_get_contents($client.urlencode($requestXML));
        $rate_info = simplexml_load_string($response);

        $total_shipping = 0;

        if($rate_info->Package->Error)
            throw new \Exception(sprintf($rate_info->Package->Error->Description));
       
        //\Log::info(json_encode($rate_info->Package->Error));

        /* tally all costs */
        foreach($rate_info->Package as $package){
            $total_shipping = floatval($package->Postage->Rate[0]) + floatval($total_shipping);
        }

        /* return 00.00 formatted number */
        return money_format('%i',$total_shipping);
    }
}
