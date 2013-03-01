<?php
    $menu[] = array(
			        'name' => trans('Radius'),
			        'img' =>'radius.png',
			        'link' =>'',
			        'tip' => trans('Radius'),
			        'accesskey' =>'r',
			        'prio' =>'50',
			        'submenu' => array(
						array(
							'name' => trans('Filter'),
							'link' => '?m=rad_traffic',
							'tip'  => trans('User-defined stats'),
							'prio' => 10,
						),
						array(
							'name' => trans('Last Hour'),
							'link' => '?m=rad_traffic&bar=hour',
							'tip'  => trans('Last hour stats for all networks'),
							'prio' => 20,
						),
						array(
							'name' => trans('Last Day'),
							'link' => '?m=rad_traffic&bar=day',
							'tip'  => trans('Last day stats for all networks'),
							'prio' => 30,
						),
						array(
							'name' => trans('Last 30 Days'),
							'link' => '?m=rad_traffic&bar=month',
							'tip'  => trans('Last month stats for all networks'),
							'prio' => 40,
						),
						array(
							'name' => trans('Last Year'),
							'link' => '?m=rad_traffic&bar=year',
							'tip'  => trans('Last year stats for all networks'),
							'prio' => 50,
						),
				        array(
					        'name' => 'Zalogowani',
					        'link' => '?m=rad_loggedin',
					        'tip'  => 'Lista zalogowanych',
					        'prio' => 60,
				        ),
				        array(
					        'name' => 'Accounting',
					        'link' => '?m=rad_accounting',
					        'tip'  => 'Accounting',
					        'prio' => 60,
				        ),
				        array(
					        'name' => 'Błędy logowania',
					        'link' => '?m=rad_badlogins',
					        'tip'  => 'Lista błędów logowania',
					        'prio' => 70,
				        ),
				        array(
					        'name' => 'Flapujące sesje',
					        'link' => '?m=rad_flapping',
					        'tip' => 'Lista flapujących sesji',
					        'prio' => 80,
				        ),
				        
			    	),				     
    		);
?>
