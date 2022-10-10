<?php

    namespace BbqData\Models;

    use ChefPayment\Helpers\Url;
    use BbqData\Helpers\Price;
    use BbqData\Contracts\Model;
    use BbqOrders\Payment\Transaction;

    class Payment extends Model{

    
        /**
         * Payment table
         *
         * @var string
         */
        protected $table = 'payments';

        /**
         * Which properties are mass-fillable?
         * 
         * @var Array
         */
        protected $fillable = [
            'amount',
            'status',
            'method',
            'is_recurring',
            'transaction_id',
            'transaction_cost'
        ];


        /**
         * The external payment object
         *
         * @var Mollie\Api\Resources\Payment
         */
        protected $payment_object;

        /**
         * A payment belongs to an order
         *
         * @return void
         */
        public function order()
        {
            return $this->belongsTo('BbqData\Models\Order');
        }
        
        /**
         * A payment may have a membership
         *
         * @return void
         */
        public function membership()
        {
            return $this->hasOne('BbqData\Models\Membership');
        }

        /**
         * Get the amount attribute
         *
         * @return Float
         */
        public function getAmountAttribute( $value )
        {
            return $value / 100;
        }

        /**
         * Get the transaction cost attribute
         *
         * @param Int $value
         * @return Float
         */
        public function getTransactionCostAttribute( $value )
        {
            if( $value > 0 ){
                return $value / 100;
            }

            return $value;
        }

        /**
         * Format the price total
         *
         * @return void
         */
        public function getTotalAttribute()
        {
            return Price::format( $this->amount );
        }

        /**
         * Set the current payment object
         *
         * @param String $payment_object
         * @return void
         */
        public function setTransaction( $payment_object ) 
        {
            $this->payment_object = $payment_object;
            return $this;
        }

        /**
         * Set the status of this payment
         *
         * @param String $status
         * @return Payment
         */
        public function setStatus( $status ) 
        {
            $this->status = $status;
            $this->save();   
            return $this; 
        }

        
        /**
         * Get payment object
         *
         * @return Mollie\Api\Resources\Payment
         */
        public function getTransactionAttribute()
        {
            //if there's a payment object set:
            if( isset( $this->payment_object ) && !is_null( $this->payment_object ) ){
                return $this->payment_object;
            
            //if not, see if we can fetch it: 
            }else{

                $transaction = $this->transaction()->get();

                //if we have a transaction
                if( !is_null( $transaction ) ){
                    $this->setTransaction( $transaction ); //set the object
                    return $transaction; //and return it
                }
            }

            return null;
        }

        /**
         * Return an instance of the transaction helper
         */
        public function transaction()
        {
            return new Transaction( $this );
        }


        /**
         * Returns the redirect url
         *
         * @return array
         */
        public function getRedirectArray()
        {
            $redirect = [
                'error' => false,
                'redirect' => true,
                'redirect_url' => $this->getRedirectUrl()
            ];

            $redirect = apply_filters( 'chef_payment_redirect_array', $redirect, $this );
            return $redirect;
        }

        /**
         * Returns the url to which to redirect
         * 
         * @return String
         */
        public function getRedirectUrl()
        {
            $thanks = \get_route( 'thanks' );
            $thanks = add_query_arg( 'order', $this->order->order_number, $thanks );

            if( !$this->needsPaymentRedirect() ){
                $url = $thanks;
            }else{
                $url = $this->transaction->getPaymentUrl() ?? null;
            }

            $url = apply_filters( 'chef_payment_redirect_url', $url, $this );
            return $url;
        }


        /**
         * Check if this payment needs a redirect
         *
         * @return void
         */
        public function needsPaymentRedirect()
        {
            if( $this->method == 'bank-transfer' || $this->amount <= 0 ){
                return false;
            }

            return true;
        }


        /**
         * Is this payment a bank transfer? 
         *
         * @return boolean
         */
        public function isBankTransfer()
        {
            if( $this->method == 'bank-transfer' || $this->method == 'banktrans' ){
                return true;
            }

            return false;
        }
        

        /**
         * Get the payment status
         *
         * @return void
         */
        public function fetchStatus()
        {
            //fetch the fresh status of this payment
            if( !is_null( $this->transaction_id ) ){
                
            }
        }


        /**
         * Translate multisafe pay statusses to our payment statusses.
         *
         * @param string $status
         * @return string
         */
        public function translate_status( $status )
        {
            switch( $status ){

                case 'initialized':
                    return 'open';
                    break;
                case 'uncleared':
                    return 'on-hold';
                    break;
                case 'completed':
                case 'shipped':
                    return 'paid';
                    break;

                case 'declined':
                case 'void':
                    return 'cancelled';
                    break;
                case 'refund':
                case 'chargedback':
                case 'partial_refunded':
                    return 'refund';
                default: 
                    return $status;
                    break;
            }   
        }
        
    }