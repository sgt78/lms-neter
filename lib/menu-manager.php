<?php
	$menu['manager']  = array(
			'name'	=> 'Manager',
			'img'	=> 'money.gif',
			'tip'	=> 'Neter Manager',
			'prio'	=> 26,
			'submenu'	=> array(
				array(
					'name' => trans('Konta Księgowe'),
					'link' => '?m=nm_konta_ksiegowe',
					'tip' => trans('Lista kont księgowych'),
				),
				array(
					'name' => trans('Koszty stałe'),
					'link' => '?m=nm_koszty_stale',
					'tip' => trans('Lista kosztów stałych/cyklicznych'),
				),
				array(
					'name' => 'Lista Dostawców',
					'link' => '?m=nm_dostawcy_lista',
					'tip'  => 'Lista dostawców',
				),
				array(
					'name' => 'Faktury kosztowe',
					'link' => '?m=nm_fv_kosztowe_lista',
					'tip' => trans('List of invoices'),
				),
				array(
					'name' => 'Nowa fv kosztowa',
					'link' => '?m=nm_fv_kosztowa_dodaj&action=init',
					'tip' => trans('Generate invoice'),
				),
			),				
	);
	
	$menu['netdevices']['submenu'][] = array(
		'name' => trans('Google Map'),
		'link' => '?m=netdevmapgoogle',
		'tip' => trans('Network map display'),
		'prio' => 40,
	);

	$menu['timetable']['submenu'][] = array(
		'name' => 'Punktacja',
		'link' => '?m=eventscore',
		'tip' => 'Punktacja wykonywanych prac',
		'prio' => 50,
	);

	$menu['timetable']['submenu'][] = array(
		'name' => 'Raporty',
		'link' => '?m=eventsreports',
		'tip' => 'Raporty wykonywanych prac',
		'prio' => 60,
	);
	
	$menu['helpdesk']['submenu'][] = array(
		'name' => 'Zadania',
		'link' => '?m=rtticketsstatus',
		'tip'  => 'Lista zadań do wykonania',
		'prio' => 60
	);

?>
