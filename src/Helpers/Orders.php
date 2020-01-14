<?php
namespace LaravelShipStation\Helpers;

class Orders extends Endpoint
{
    /**
     * Create a single order in ShipStation.
     *
     * @param  array  $order
     * @return \stdClass
     */
    public function create($order)
    {
        return $this->post($order, 'createorder');
    }

    /**
     * Does the specified order exist by the given order number?
     *
     * @param  mixed  $orderNumber
     * @return bool
     */
    public function existsByOrderNumber($orderNumber)
    {
        return (bool)$this->getOrderId($orderNumber);
    }

    /**
     * How many orders are awaiting shipment?
     *
     * @return int|null
     */
    public function awaitingShipmentCount()
    {
        return ($this->get(['orderStatus' => 'awaiting_shipment']))->total ?? null;
    }
}
