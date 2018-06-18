<?php
/**
 * Order Stats class.
 *
 * @package     EDD
 * @subpackage  Orders
 * @copyright   Copyright (c) 2018, Easy Digital Downloads, LLC
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */
namespace EDD\Orders;

use EDD\Reports as Reports;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

/**
 * Stats Class.
 *
 * @since 3.0
 */
class Stats {

	/**
	 * Parsed query vars.
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $query_vars = array();

	/**
	 * Date ranges.
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $date_ranges = array();

	/**
	 * Query var originals. These hold query vars passed to the constructor.
	 *
	 * @since 3.0
	 * @access protected
	 * @var array
	 */
	protected $query_var_originals = array();

	/**
	 * Constructor.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class. Some methods will not allow parameters to be overridden as it could lead to inaccurate calculations.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Certain methods will only accept certain functions. See each method for
	 *                             a list of accepted SQL functions.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 */
	public function __construct( $query = array() ) {

		// Start the Reports API.
		new Reports\Init();

		// Set date ranges.
		$this->set_date_ranges();

		// Maybe parse query.
		if ( ! empty( $query ) ) {
			$this->parse_query( $query );

		// Set defaults.
		} else {
			$this->query_var_originals = $this->query_vars = array(
				'start'             => '',
				'end'               => '',
				'range'             => '',
				'where_sql'         => '',
				'date_query_sql'    => '',
				'date_query_column' => '',
				'column'            => '',
				'table'             => '',
				'function'          => 'SUM',
				'output'            => 'raw',
			);
		}
	}

	/** Calculation Methods ***************************************************/

	/** Orders ***************************************************************/

	/**
	 * Calculate order earnings.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Default `SUM`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return string Formatted order earnings.
	 */
	public function get_order_earnings( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'total';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$function = isset( $this->query_vars['function'] )
			? $this->query_vars['function'] . "({$this->query_vars['column']})"
			: "SUM({$this->query_vars['column']})";

		$sql = "SELECT {$function}
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0.00
			: (float) $result;

		// Reset query vars.
		$this->post_query();

		return $this->maybe_format( $total );
	}

	/**
	 * Calculate the number of orders.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Accepts `COUNT` and `AVG`. Default `COUNT`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int Number of orders.
	 */
	public function get_order_count( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		// Only `COUNT` and `AVG` are accepted by this method.
		$accepted_functions = array( 'COUNT', 'AVG' );

		$function = isset( $this->query_vars['function'] ) && in_array( strtoupper( $this->query_vars['function'] ), $accepted_functions, true )
			? $this->query_vars['function'] . "({$this->query_vars['column']})"
			: 'COUNT(id)';

		$sql = "SELECT {$function}
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0
			: absint( $result );

		// Reset query vars.
		$this->post_query();

		return $total;
	}

	/**
	 * Calculate number of refunded orders.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_order_count()
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Accepts `COUNT` and `AVG`. Default `COUNT`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int Number of refunded orders.
	 */
	public function get_order_refund_count( $query = array() ) {
		$this->query_vars['where_sql'] = $this->get_db()->prepare( 'AND status = %s', 'refunded' );

		return $this->get_order_count( $query );
	}

	/**
	 * Calculate total amount for refunded orders.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_order_earnings()
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Default `SUM`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return string Formatted amount from refunded orders.
	 */
	public function get_order_refund_amount( $query = array() ) {
		$this->query_vars['where_sql'] = $this->get_db()->prepare( 'AND status = %s', 'refunded' );

		return $this->get_order_earnings( $query );
	}

	/**
	 * Calculate average time for an order to be refunded.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_order_earnings()
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Accepts `AVG` only. Default `AVG`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return string Average time for an order to be refunded.
	 */
	public function get_average_refund_time( $query = array() ) {
		// TODO: Implement as per partial refunds.
	}

	/**
	 * Calculate refund rate.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return float|int Rate of refunded orders.
	 */
	public function get_refund_rate( $query ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$status_sql = $this->get_db()->prepare( 'AND status = %s', 'refunded' );

		$sql = "SELECT COUNT(id) / o.total * 100 AS `refund_rate`
				FROM {$this->query_vars['table']}
				CROSS JOIN (
					SELECT COUNT(id) AS total
					FROM {$this->query_vars['table']}
					WHERE 1=1 {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}
				) o
				WHERE 1=1 {$status_sql} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0
			: round( $result, 2 );

		// Reset query vars.
		$this->post_query();

		return $total;
	}

	/** Order Item ************************************************************/

	/**
	 * Calculate order item earnings.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start      Start day and time (based on the beginning of the given day).
	 *     @type string $end        End day and time (based on the end of the given day).
	 *     @type string $range      Date range. If a range is passed, this will override and `start` and `end`
	 *                              values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function   SQL function. Default `SUM`.
	 *     @type string $where_sql  Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                              query.
	 *     @type int    $product_id Product ID. If empty, an aggregation of the values in the `total` column in the
	 *                              `edd_order_items` table will be returned.
	 *     @type string $output     The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return float|int Formatted order item earnings.
	 */
	public function get_order_item_earnings( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_order_items;
		$this->query_vars['column']            = 'total';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$function = isset( $this->query_vars['function'] )
			? $this->query_vars['function'] . "({$this->query_vars['column']})"
			: "SUM({$this->query_vars['column']})";

		$product_id = isset( $this->query_vars['product_id'] )
			? $this->get_db()->prepare( 'AND product_id = %d', absint( $this->query_vars['product_id'] ) )
			: '';

		$sql = "SELECT {$function}
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$product_id} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0.00
			: (float) $result;

		// Reset query vars.
		$this->post_query();

		return $this->maybe_format( $total );
	}

	/**
	 * Calculate the number of times a specific item has been purchased.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start      Start day and time (based on the beginning of the given day).
	 *     @type string $end        End day and time (based on the end of the given day).
	 *     @type string $range      Date range. If a range is passed, this will override and `start` and `end`
	 *                              values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function   SQL function. Accepts `COUNT` and `AVG`. Default `COUNT`.
	 *     @type string $where_sql  Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                              query.
	 *     @type int    $product_id Product ID. If empty, an aggregation of the values in the `total` column in the
	 *                              `edd_order_items` table will be returned.
	 *     @type string $output     The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int Number of times a specific item has been purchased.
	 */
	public function get_order_item_count( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_order_items;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		// Only `COUNT` and `AVG` are accepted by this method.
		$accepted_functions = array( 'COUNT', 'AVG' );

		$function = isset( $this->query_vars['function'] ) && in_array( strtoupper( $this->query_vars['function'] ), $accepted_functions, true )
			? $this->query_vars['function'] . "({$this->query_vars['column']})"
			: 'COUNT(id)';

		$product_id = isset( $this->query_vars['product_id'] )
			? $this->get_db()->prepare( 'AND product_id = %d', absint( $this->query_vars['product_id'] ) )
			: '';

		// Calculating an average requires a subquery.
		if ( 'AVG' === $this->query_vars['function'] ) {
			$sql = "SELECT AVG(id)
					FROM (
						SELECT COUNT(id) AS id
						FROM {$this->query_vars['table']}
						WHERE 1=1 {$product_id} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}
						GROUP BY order_id
					) AS counts";
		} else {
			$sql = "SELECT {$function}
					FROM {$this->query_vars['table']}
					WHERE 1=1 {$product_id} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";
		}

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0
			: absint( $result );

		// Reset query vars.
		$this->post_query();

		return $total;
	}

	/**
	 * Calculate most valuable order items.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended to the
	 *                             query.
	 *     @type int    $number    Number of order items to fetch. Default 1.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return array Array of objects with most valuable order items. Each object has the product ID, total earnings,
	 *               and an instance of EDD_Download.
	 */
	public function get_most_valuable_order_items( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_order_items;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		// By default, the most valuable customer is returned.
		$number = isset( $this->query_vars['number'] )
			? absint( $this->query_vars['number'] )
			: 1;

		$sql = "SELECT product_id, SUM(total) AS total
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}
				GROUP BY product_id
				ORDER BY total DESC
				LIMIT {$number}";

		$result = $this->get_db()->get_row( $sql );

		// Format resultant object.
		$result->product_id = absint( $result->product_id );
		$result->total      = $this->maybe_format( $result->total );

		// Add instance of EDD_Download to resultant object.
		$result->object = edd_get_download( $result->product_id );

		// Reset query vars.
		$this->post_query();

		return $result;
	}

	/** Discounts ************************************************************/

	/**
	 * Calculate the usage count of discount codes.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start         Start day and time (based on the beginning of the given day).
	 *     @type string $end           End day and time (based on the end of the given day).
	 *     @type string $range         Date range. If a range is passed, this will override and `start` and `end`
	 *                                 values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function      This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql     Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                                 to the query.
	 *     @type string $discount_code Discount code to fetch the usage count for.
	 *     @type string $output        The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int Number of times a discount code has been used.
	 */
	public function get_discount_usage_count( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_order_adjustments;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$discount_code = isset( $this->query_vars['discount_code'] )
			? $this->get_db()->prepare( 'AND type = %s AND description = %s', 'discount', sanitize_text_field( $this->query_vars['discount_code'] ) )
			: $this->get_db()->prepare( 'AND type = %s', 'discount' );

		$sql = "SELECT COUNT({$this->query_vars['column']})
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$discount_code} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0
			: absint( $result );

		// Reset query vars.
		$this->post_query();

		return $total;
	}

	/**
	 * Calculate the savings from using a discount code.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start         Start day and time (based on the beginning of the given day).
	 *     @type string $end           End day and time (based on the end of the given day).
	 *     @type string $range         Date range. If a range is passed, this will override and `start` and `end`
	 *                                 values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function      This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql     Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                                 to the query.
	 *     @type string $discount_code Discount code to fetch the savings amount for. Default empty. If empty, the amount
	 *                                 saved from using any discount will be returned.
	 *     @type string $output        The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return float Savings from using a discount code.
	 */
	public function get_discount_savings( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_order_adjustments;
		$this->query_vars['column']            = 'amount';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$discount_code = isset( $this->query_vars['discount_code'] )
			? $this->get_db()->prepare( 'AND type = %s AND description = %s', 'discount', sanitize_text_field( $this->query_vars['discount_code'] ) )
			: $this->get_db()->prepare( 'AND type = %s', 'discount' );

		$sql = "SELECT SUM({$this->query_vars['column']})
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$discount_code} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0.00
			: floatval( $result );

		// Reset query vars.
		$this->post_query();

		return $this->maybe_format( $total );
	}

	/**
	 * Calculate the average discount amount applied to an order.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                             to the query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return float Average discount amount applied to an order.
	 */
	public function get_average_discount_amount( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_order_adjustments;
		$this->query_vars['column']            = 'amount';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$type_discount = $this->get_db()->prepare( 'AND type = %s', 'discount' );

		$sql = "SELECT AVG({$this->query_vars['column']})
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$type_discount} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0.00
			: floatval( $result );

		// Reset query vars.
		$this->post_query();

		return $this->maybe_format( $total );
	}

	/**
	 * Calculate the ratio of discounted to non-discounted orders.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                             to the query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return string Ratio of discounted to non-discounted orders. Format is A:B where A and B are integers.
	 */
	public function get_ratio_of_discounted_orders( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$sql = "SELECT COUNT(id) AS total, o.discounted_orders
				FROM {$this->query_vars['table']}
				CROSS JOIN (
					SELECT COUNT(id) AS discounted_orders
					FROM {$this->query_vars['table']}
					WHERE 1=1 AND discount > 0 {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}
				) o
				WHERE 1=1 {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_row( $sql );

		// No need to calculate the ratio if there are no discounted orders.
		if ( 0 === (int) $result->discounted_orders ) {
			return $result->discounted_orders . ':' . $result->total;
		}

		// Calculate GCD.
		$result->total             = absint( $result->total );
		$result->discounted_orders = absint( $result->discounted_orders );

		$original_result = clone $result;

		while ( 0 !== $result->total ) {
			$remainder                 = $result->discounted_orders % $result->total;
			$result->discounted_orders = $result->total;
			$result->total             = $remainder;
		}

		$ratio = absint( $result->discounted_orders );

		// Reset query vars.
		$this->post_query();

		// Return the formatted ratio.
		return ( $original_result->discounted_orders / $ratio ) . ':' . ( $original_result->total / $ratio );
	}

	/** Gateways *************************************************************/

	/**
	 * Perform gateway calculations based on data passed.
	 *
	 * @internal This method must remain `private`, it exists to reduce duplicated code.
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Accepts `COUNT`, `AVG`, and `SUM`. Default `COUNT`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                             to the query.
	 *     @type string $gateway   Gateway name. This is checked against a list of registered payment gateways.
	 *                             If a gateway is not passed, a list of objects are returned for each gateway and the
	 *                             number of orders processed with that gateway.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return array List of objects containing data pertinent to the query parameters passed.
	 */
	private function get_gateway_data( $query = array() ) {

		// Set up default values.
		$gateways = edd_get_payment_gateways();
		$defaults = array();

		// Set up an object for each gateway.
		foreach ( $gateways as $id => $data ) {
			$object          = new \stdClass();
			$object->gateway = $id;
			$object->count   = 0;

			$defaults[] = $object;
		}

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'total';
		$this->query_vars['date_query_column'] = 'date_created';
		$this->query_vars['function']          = 'COUNT';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		// Only `COUNT`, `AVG` and `SUM` are accepted by this method.
		$accepted_functions = array( 'COUNT', 'AVG', 'SUM' );

		$function = isset( $this->query_vars['function'] ) && in_array( strtoupper( $this->query_vars['function'] ), $accepted_functions, true )
			? $this->query_vars['function'] . "({$this->query_vars['column']})"
			: 'COUNT(id)';

		$gateway = isset( $this->query_vars['gateway'] )
			? $this->get_db()->prepare( 'AND gateway = %s', sanitize_text_field( $this->query_vars['gateway'] ) )
			: '';

		$sql = "SELECT gateway, {$function} AS count
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$gateway} {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}
				GROUP BY gateway";

		$result = $this->get_db()->get_results( $sql );

		// Ensure count values are always valid integers if counting sales.
		if ( 'COUNT' === $this->query_vars['function'] ) {
			array_walk( $result, function ( &$value ) {
				$value->count = absint( $value->count );
			} );
		}

		$results = array();

		// Merge defaults with values returned from the database.
		foreach ( $defaults as $key => $value ) {

			// Filter based on gateway.
			$filter = wp_filter_object_list( $result, array( 'gateway' => $value->gateway ) );

			$filter = ! empty( $filter )
				? array_values( $filter )
				: array();

			if ( ! empty( $filter ) ) {
				$results[] = $filter[0];
			} else {
				$results[] = $defaults[ $key ];
			}
		}

		if ( ! empty( $gateway ) ) {

			// Filter based on gateway if passed.
			$filter = wp_filter_object_list( $result, array( 'gateway' => $this->query_vars['gateway'] ) );

			// Return number of sales for gateway passed.
			return absint( $filter[0]->count );
		}

		// Reset query vars.
		$this->post_query();

		// Return array of objects with gateway name and count.
		return $results;
	}

	/**
	 * Calculate the number of processed by a gateway.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_gateway_data()
	 *
	 * @param array $query See \EDD\Orders\Stats::get_gateway_data().
	 *
	 * @return array List of objects containing the number of sales processed either for every gateway or the gateway
	 *               passed as a query parameter.
	 */
	public function get_gateway_sales( $query = array() ) {

		// Dispatch to \EDD\Orders\Stats::get_gateway_data().
		return $this->get_gateway_data( $query );
	}

	/**
	 * Calculate the total order amount of processed by a gateway.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_gateway_data()
	 *
	 * @param array $query See \EDD\Orders\Stats::get_gateway_data().
	 *
	 * @return array List of objects containing the amount processed either for every gateway or the gateway
	 *               passed as a query parameter.
	 */
	public function get_gateway_earnings( $query = array() ) {

		// Summation is required as we are returning earnings.
		$query['function'] = 'SUM';

		// Dispatch to \EDD\Orders\Stats::get_gateway_data().
		$result = $this->get_gateway_data( $query );

		// Rename object var.
		array_walk( $result, function( &$value ) {
			$value->earnings = $value->count;
			$value->earnings = $this->maybe_format( $value->earnings );
			unset( $value->count );
		} );

		// Reset query vars.
		$this->post_query();

		// Return array of objects with gateway name and earnings.
		return $result;
	}

	/**
	 * Calculate the amount for refunded orders processed by a gateway.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_gateway_earnings()
	 *
	 * @param array $query See \EDD\Orders\Stats::get_gateway_earnings().
	 *
	 * @return array List of objects containing the amount for refunded orders processed either for every
	 *               gateway or the gateway passed as a query parameter.
	 */
	public function get_gateway_refund_amount( $query = array() ) {

		// Ensure orders are refunded.
		$this->query_vars['where_sql'] = $this->get_db()->prepare( 'AND status = %s', 'refunded' );

		// Dispatch to \EDD\Orders\Stats::get_gateway_data().
		$result = $this->get_gateway_earnings( $query );

		// Reset query vars.
		$this->post_query();

		// Return array of objects with gateway name and amount from refunded orders.
		return $result;
	}

	/**
	 * Calculate the average order amount of processed by a gateway.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_gateway_data()
	 *
	 * @param array $query See \EDD\Orders\Stats::get_gateway_data().
	 *
	 * @return array List of objects containing the average order value processed either for every gateway
	 *               pr the gateway passed as a query parameter.
	 */
	public function get_gateway_average_value( $query = array() ) {

		// Function needs to be `AVG`.
		$query['function'] = 'AVG';

		// Dispatch to \EDD\Orders\Stats::get_gateway_data().
		$result = $this->get_gateway_data( $query );

		// Rename object var.
		array_walk( $result, function( &$value ) {
			$value->earnings = $value->count;
			$value->earnings = $this->maybe_format( $value->earnings );
			unset( $value->count );
		} );

		// Reset query vars.
		$this->post_query();

		// Return array of objects with gateway name and earnings.
		return $result;
	}

	/** Tax ******************************************************************/

	/**
	 * Calculate total tax collected.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Default `COUNT`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                             to the query.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return string Formatted amount of total tax collected.
	 */
	public function get_tax( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'tax';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$function = isset( $this->query_vars['function'] )
			? $this->query_vars['function'] . "({$this->query_vars['column']})"
			: "SUM({$this->query_vars['column']})";

		$sql = "SELECT {$function}
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0.00
			: (float) $result;

		// Reset query vars.
		$this->post_query();

		return $this->maybe_format( $total );
	}

	/**
	 * Calculate total tax collected for country and state passed.
	 *
	 * TODO: Finish implementation.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start     Start day and time (based on the beginning of the given day).
	 *     @type string $end       End day and time (based on the end of the given day).
	 *     @type string $range     Date range. If a range is passed, this will override and `start` and `end`
	 *                             values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function  SQL function. Default `COUNT`.
	 *     @type string $where_sql Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                             to the query.
	 *     @type string $country   Country name. Defaults to store's base country.
	 *     @type string $state     State name. Defaults to store's base state.
	 *     @type string $output    The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return string Formatted amount of total tax collected for country and state passed.
	 */
	public function get_tax_by_location( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'tax';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		/** Parse country ****************************************************/

		$country_list = array_filter( edd_get_country_list() );

		$country = isset( $this->query_vars['country'] )
			? sanitize_text_field( $this->query_vars['country'] )
			: edd_get_shop_country();

		// Maybe convert country code to country name.
		$country = in_array( $country, array_flip( $country_list ), true )
			? $country_list[ $country ]
			: $country;

		// Ensure a valid county has been passed.
		$country = in_array( $country, $country_list, true )
			? $country
			: null;

		// Bail early if country does not exist.
		if ( is_null( $country ) ) {
			return 0.00;
		}

		/** Parse state ******************************************************/

		$state = isset( $this->query_vars['state'] )
			? sanitize_text_field( $this->query_vars['state'] )
			: edd_get_shop_state();

		// Only parse state if one was passed.
		if ( $state ) {
			$country_codes = array_flip( $country_list );

			$state_list = array_filter( edd_get_shop_states( $country_codes[ $country ] ) );

			// Maybe convert state code to state name.
			$state = in_array( $state, array_flip( $state_list ), true )
				? $state_list[ $state ]
				: $state;

			// Ensure a valid county has been passed.
			$state = in_array( $state, $state_list, true )
				? $state
				: null;
		}

		// Reset query vars.
		$this->post_query();

		// Bail early if state does not exist.
		if ( null === $state ) {
			return 0.00;
		}
	}

	/** Customers ************************************************************/

	/**
	 * Calculate the lifetime value of a customer.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start       Start day and time (based on the beginning of the given day).
	 *     @type string $end         End day and time (based on the end of the given day).
	 *     @type string $range       Date range. If a range is passed, this will override and `start` and `end`
	 *                               values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function    SQL function. Accepts `AVG` and `SUM`. Default `SUM`.
	 *     @type string $where_sql   Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                               to the query.
	 *     @type int    $customer_id Customer ID. Default empty.
	 *     @type int    $user_id     User ID. Default empty.
	 *     @type string $email       Email address.
	 *     @type string $output      The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return string Formatted lifetime value of a customer.
	 */
	public function get_customer_lifetime_value( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'total';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		// Only `AVG` and `SUM` are accepted by this method.
		$accepted_functions = array( 'AVG', 'SUM' );

		$function = isset( $this->query_vars['function'] ) && in_array( strtoupper( $this->query_vars['function'] ), $accepted_functions, true )
			? $this->query_vars['function'] . "({$this->query_vars['column']})"
			: "SUM({$this->query_vars['column']})";

		$user = isset( $this->query_vars['user_id'] )
			? $this->get_db()->prepare( 'AND user_id = %d', absint( $this->query_vars['user_id'] ) )
			: '';

		$customer = isset( $this->query_vars['customer'] )
			? $this->get_db()->prepare( 'AND customer_id = %d', absint( $this->query_vars['customer'] ) )
			: '';

		$email = isset( $this->query_vars['email'] )
			? $this->get_db()->prepare( 'AND email = %s', absint( $this->query_vars['email'] ) )
			: '';

		$sql = "SELECT {$function}
				FROM (
					SELECT SUM(total) AS total
					FROM {$this->query_vars['table']}
					WHERE 1=1 {$user} {$customer} {$email} {$this->query_vars['date_query_sql']}
				  	GROUP BY customer_id
				) o";

		$result = $this->get_db()->get_var( $sql );

		$total = null === $result
			? 0.00
			: (float) $result;

		// Reset query vars.
		$this->post_query();

		return $this->maybe_format( $total );
	}

	/**
	 * Calculate the number of orders made by a customer.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_order_count()
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start       Start day and time (based on the beginning of the given day).
	 *     @type string $end         End day and time (based on the end of the given day).
	 *     @type string $range       Date range. If a range is passed, this will override and `start` and `end`
	 *                               values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function    SQL function. Accepts `AVG` and `SUM`. Default `SUM`.
	 *     @type string $where_sql   Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                               to the query.
	 *     @type int    $customer_id Customer ID. Default empty.
	 *     @type int    $user_id     User ID. Default empty.
	 *     @type string $email       Email address.
	 *     @type string $output      The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int Number of orders made by a customer.
	 */
	public function get_customer_order_count( $query = array() ) {
		$user = isset( $this->query_vars['user_id'] )
			? $this->get_db()->prepare( 'AND user_id = %d', absint( $this->query_vars['user_id'] ) )
			: '';

		$customer = isset( $this->query_vars['customer'] )
			? $this->get_db()->prepare( 'AND customer_id = %d', absint( $this->query_vars['customer'] ) )
			: '';

		$email = isset( $this->query_vars['email'] )
			? $this->get_db()->prepare( 'AND email = %s', absint( $this->query_vars['email'] ) )
			: '';

		$query['where_sql'] = "{$user} {$customer} {$email}";

		// Dispatch to \EDD\Orders\Stats::get_order_count().
		return $this->get_order_count( $query );
	}

	/**
	 * Calculate the average age of a customer.
	 *
	 * @since 3.0
	 *
	 * @see \EDD\Orders\Stats::get_order_count()
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start       Start day and time (based on the beginning of the given day).
	 *     @type string $end         End day and time (based on the end of the given day).
	 *     @type string $range       Date range. If a range is passed, this will override and `start` and `end`
	 *                               values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function    This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql   Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                               to the query.
	 *     @type string $output      The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return int|float Average age of a customer.
	 */
	public function get_customer_age( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_customers;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		$sql = "SELECT AVG(DATEDIFF(NOW(), date_created))
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$this->query_vars['date_query_sql']}";

		$result = $this->get_db()->get_var( $sql );

		// Reset query vars.
		$this->post_query();

		return null === $result
			? 0
			: round( $result, 2 );
	}

	/**
	 * Calculate the most valuable customers.
	 *
	 * @since 3.0
	 *
	 * @param array $query {
	 *     Optional. Array of query parameters.
	 *     Default empty.
	 *
	 *     Each method accepts query parameters to be passed. Parameters passed to methods override the ones passed in
	 *     the constructor. This is by design to allow for multiple calculations to be executed from one instance of
	 *     this class.
	 *
	 *     @type string $start       Start day and time (based on the beginning of the given day).
	 *     @type string $end         End day and time (based on the end of the given day).
	 *     @type string $range       Date range. If a range is passed, this will override and `start` and `end`
	 *                               values passed. See \EDD\Reports\get_dates_filter_options() for valid date ranges.
	 *     @type string $function    This method does not allow any SQL functions to be passed.
	 *     @type string $where_sql   Reserved for internal use. Allows for additional WHERE clauses to be appended
	 *                               to the query.
	 *     @type int    $number      Number of customers to fetch. Default 1.
	 *     @type string $output      The output format of the calculation. Accepts `raw` and `formatted`. Default `raw`.
	 * }
	 *
	 * @return array Array of objects with most valuable customers. Each object has the customer ID, total amount spent
	 *               by that customer and an instance of EDD_Customer.
	 */
	public function get_most_valuable_customers( $query = array() ) {

		// Add table and column name to query_vars to assist with date query generation.
		$this->query_vars['table']             = $this->get_db()->edd_orders;
		$this->query_vars['column']            = 'id';
		$this->query_vars['date_query_column'] = 'date_created';

		// Run pre-query checks and maybe generate SQL.
		$this->pre_query( $query );

		// By default, the most valuable customer is returned.
		$number = isset( $this->query_vars['number'] )
			? absint( $this->query_vars['number'] )
			: 1;

		$sql = "SELECT customer_id, SUM(total) AS total
				FROM {$this->query_vars['table']}
				WHERE 1=1 {$this->query_vars['where_sql']} {$this->query_vars['date_query_sql']}
				GROUP BY customer_id
				ORDER BY total DESC
				LIMIT {$number}";

		$result = $this->get_db()->get_results( $sql );

		array_walk( $result, function ( &$value ) {

			// Format resultant object.
			$value->customer_id = absint( $value->customer_id );
			$value->total       = $this->maybe_format( $value->total );

			// Add instance of EDD_Download to resultant object.
			$value->object = edd_get_customer( $value->customer_id );
		} );

		// Reset query vars.
		$this->post_query();

		return $result;
	}

	/** Private Methods ******************************************************/

	/**
	 * Parse query vars to be passed to the calculation methods.
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @see \EDD\Orders\Stats::__construct()
	 *
	 * @param array $query Array of arguments. See \EDD\Orders\Stats::__construct().
	 */
	private function parse_query( $query = array() ) {
		$query_var_defaults = array(
			'start'             => '',
			'end'               => '',
			'range'             => '',
			'where_sql'         => '',
			'date_query_sql'    => '',
			'date_query_column' => '',
			'column'            => '',
			'table'             => '',
			'function'          => 'SUM',
			'output'            => 'raw',
		);

		if ( empty( $this->query_vars ) ) {
			$this->query_vars = wp_parse_args( $query, $query_var_defaults );
		} else {
			$this->query_vars = wp_parse_args( $query, $this->query_vars );
		}

		// Use Carbon to set up start and end date based on range passed.
		if ( ! empty( $this->query_vars['range'] ) && isset( $this->date_ranges[ $this->query_vars['range'] ] ) ) {
			$this->query_vars['start'] = $this->date_ranges[ $this->query_vars['range'] ]['start']->format( 'mysql' );
			$this->query_vars['end']   = $this->date_ranges[ $this->query_vars['range'] ]['end']->format( 'mysql' );
		}

		// Correctly format functions and column names.
		if ( ! empty( $this->query_vars['function'] ) ) {
			$this->query_vars['function'] = strtoupper( $this->query_vars['function'] );
		}

		if ( ! empty( $this->query_vars['column'] ) ) {
			$this->query_vars['column'] = strtolower( $this->query_vars['column'] );
		}

		/**
		 * Fires after the item query vars have been parsed.
		 *
		 * @since 3.0
		 *
		 * @param \EDD\Orders\Stats &$this The \EDD\Orders\Stats (passed by reference).
		 */
		do_action_ref_array( 'edd_order_stats_parse_query', array( &$this ) );
	}

	/**
	 * Ensures arguments exist before going ahead and calculating statistics.
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param array $query
	 */
	private function pre_query( $query = array() ) {

		// Maybe parse query.
		if ( ! empty( $query ) ) {
			$this->parse_query( $query );
		}

		// Generate date query SQL if dates have been set.
		if ( ! empty( $this->query_vars['start'] ) || ! empty( $this->query_vars['end'] ) ) {
			$date_query_sql = "AND {$this->query_vars['table']}.{$this->query_vars['date_query_column']} ";

			if ( ! empty( $this->query_vars['start'] ) ) {
				$date_query_sql .= $this->get_db()->prepare( '>= %s', $this->query_vars['start'] );
			}

			// Join dates with `AND` if start and end date set.
			if ( ! empty( $this->query_vars['start'] ) && ! empty( $this->query_vars['end'] ) ) {
				$date_query_sql .= ' AND ';
			}

			if ( ! empty( $this->query_vars['end'] ) ) {
				$date_query_sql .= $this->get_db()->prepare( "{$this->query_vars['table']}.{$this->query_vars['date_query_column']} <= %s", $this->query_vars['end'] );
			}

			$this->query_vars['date_query_sql'] = $date_query_sql;
		}
	}

	/**
	 * Runs after a query. Resets query vars back to the originals passed in via the constructor.
	 *
	 * @since 3.0
	 * @access private
	 */
	private function post_query() {
		$this->query_vars = $this->query_var_originals;
	}

	/**
	 * Format the data if requested via the query parameter.
	 *
	 * @since 3.0
	 * @access private
	 *
	 * @param mixed $data Data to format.
	 *
	 * @return mixed Raw or formatted data depending on query parameter.
	 */
	private function maybe_format( $data = null ) {

		// Bail if nothing was passed.
		if ( empty( $data ) || null === $data ) {
			return $data;
		}

		$allowed_output_formats = array( 'raw', 'formatted' );

		// Output format. Default raw.
		$output = isset( $this->query_vars['output'] ) && in_array( $this->query_vars['output'], $allowed_output_formats, true )
			? $this->query_vars['output']
			: 'raw';

		// Return data as is if the format is raw.
		if ( 'raw' === $output ) {
			return $data;
		}

		if ( is_object( $data ) ) {
			foreach ( array_keys( get_object_vars( $data ) ) as $field ) {
				if ( is_numeric( $data->{$field} ) ) {
					$data->{$field} = edd_currency_filter( edd_format_amount( $data->{$field} ) );
				}
			}
		} elseif ( is_array( $data ) ) {
			foreach ( array_keys( $data ) as $field ) {
				if ( is_numeric( $data[ $field ] ) ) {
					$data[ $field ] = edd_currency_filter( edd_format_amount( $data[ $field ] ) );
				}
			}
		} else {
			if ( is_numeric( $data ) ) {
				$data = edd_currency_filter( edd_format_amount( $data ) );
			}
		}

		return $data;
	}

	/** Private Getters *******************************************************/

	/**
	 * Return the global database interface.
	 *
	 * @since 3.0
	 * @access private
	 * @static
	 *
	 * @return \wpdb|\stdClass
	 */
	private static function get_db() {
		return isset( $GLOBALS['wpdb'] )
			? $GLOBALS['wpdb']
			: new \stdClass();
	}

	/** Private Setters ******************************************************/

	/**
	 * Set up the date ranges available.
	 *
	 * @since 3.0
	 * @access private
	 */
	private function set_date_ranges() {
		$date = EDD()->utils->date( 'now' );

		$date_filters = Reports\get_dates_filter_options();

		foreach ( $date_filters as $range => $label ) {
			$this->date_ranges[ $range ] = Reports\parse_dates_for_range( $date, $range );
		}
	}
}
