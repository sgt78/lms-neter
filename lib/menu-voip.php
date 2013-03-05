<?php
	$menu[] = array(
		'name' => 'Telefonia',
        'img' =>'phone.gif',
		'link' =>'?m=configlist',
		'tip' => 'Telefonia internetowa',
        'accesskey' =>'v',
		'prio' =>'60',
		'submenu' => array(
			array(
				'name' => 'Bilans kosztów',
				'link' => '?m=v_balance',
				'tip' => 'Bilans kosztów',
				'prio' => '5'),
		    array(
			    'name' => 'Lista abonamentów',
			    'link' =>'?m=v_tarifflist',
			    'prio' => '10'),
			array(
				'name' => 'Nowy abonament',
			    'link' => '?m=v_tariffadd',
				'tip' => 'Nowy abonament',
				'prio' => '20'),
			array(
				'name' => 'Lista cenników minut',
				'link' =>'?m=v_cennlist',
				'prio' => '30'),
			array(
				'name' => 'Nowy cennik minut',
				'link' => '?m=v_cennadd',
				'tip' => 'Nowy cennik minut',
				'prio' => '40'),
			array(
				'name' => 'Lista grup<br> &nbsp;&nbsp; cennikowych',
				'link' =>'?m=v_trunkgrplist',
				'prio' => '50'),
			array(
				'name' => 'Nowa grupa<br> &nbsp;&nbsp; cennikowa',
				'link' => '?m=v_trunkgrpadd',
				'tip' => 'Nowa grupa cennikowa',
				'prio' => '60'),
			array(
				'name' => 'Stan centrali',
				'link' => '?m=v_state',
				'tip' => 'Stan centrali',
				'prio' => '110'),
			array(
				'name' => 'CDR',
				'link' => '?m=v_cdr',
				'tip' => 'Lista połączeń wychodzących',
				'prio' => '120'),
			array(
				'name' => 'Lista stref<br> &nbsp;&nbsp; numeracyjnych',
				'link' => '?m=v_netlist',
				'tip' => 'Numery',
				'prio' => '140'),
			array(
				'name' => 'Nowa strefa<br> &nbsp;&nbsp; numeracyjna',
				'link' => '?m=v_netadd',
				'tip' => 'Numery',
				'prio' => '150'),
			array(
				'name' => 'Wzorce numerów',
				'link' => '?m=v_numbers',
				'tip' => 'Numery',
				'prio' => '160'),
			array(
				'name' => 'Przeniesienia numerów',
				'link' => '?m=v_usr_movs',
				'tip' => 'Numery',
				'prio' => '170'),
			array(
				'name' => 'Operatorzy',
				'link' => '?m=v_ops',
				'tip' => 'Operatorzy',
				'prio' => '180'),
			array(
				'name' => 'Przelicz salda klientów',
				'link' => '?m=v_checkbalance',
				'tip' => 'Salda',
				'prio' => '190'),
			array(
				'name' => 'Użycie dysku',
				'link' => '?m=v_diskusage',
				'tip' => '',
				'prio' => '200')	
         )
	);
?>
