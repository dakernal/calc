<?php  // chs
include_once('./php/prepend.php');
page_open();
set_charset();

include_class('suser');
include_class('obj');
include_class('kv');
include_class('kvtype');
include_class('section');

$d = new ndb();

//global $_debug; $_debug=1;
$err = array(); // errors
$err_mess = array(
	0	=> '������ ����������.',
	1	=> '��� �������.',
	2	=> '�������� ������ ������������� - ',
	3	=> '',
);

$kvstat_code_arr = array();
$lock_flag 	= false;
$group_lock = array(2,3); // ��� ����� ������ �����������
$user_mode	= 0; // 0 - read or 1 - edit
$user_type	= 0;
$tmid		= '';


if ($auth->roles['uu']) {
	$user_type = 1; // ����
}
if ($auth->auth['tman']['is']) {
	$user_type = 2; // manager
	$tmid = (string)$auth->auth['tman']['first'];
	$kvstat_code_arr = array('000','001', '002');
}
if ($auth->roles['chess']) {
	$user_type = 3; // ��������
	$kvstat_code_arr = array('000','001','002','003','004');
}

$view_dpk = 0; // for admin, ����, ����.���, ��������������
$view_dpk = ( count(array_intersect(array('ad','uu','uum','tm'), array_keys($auth->roles))) > 0 ) ? 1 : 0;

/*
if ($auth->roles['ad']) {
	$user_type = 3; // ��������
	$kvstat_code_arr = array('000','001','002','003','004');
}
*/


if ($user_type && in_array($user_type, $group_lock)) { // default lock
	if  (false === ($lock_flag = send_term($kvid, 'lock'))) {
		$err[]	= $err_mess[0];
	}
	else if ($lock_flag === 0) {
		$suserid_lock = $d->cc("
			declare @xid numeric
			select @xid = $kvid
			select suser_id from kvde where kv_id = @xid
		");
		$su = new suser($suserid_lock);
		$suser_title = $su->sname();
		$err[]	= $err_mess[2] . $suser_title;
	}
	else { // ���� ���������� �������� �������
		$user_mode = 1;
	}
}

//������ ������� 
	// ����������
	$tpl = new fasttemplate($_TPLPATH);
	$tpl->define(array(content 	=> is_rur() ? 'chs.tpl':'chs_norur.tpl'));

	$tpl->define_dynamic('js_refresh',	'content');

	$tpl->define_dynamic('err',			'content');
	$tpl->define_dynamic('err_item',	'content');

	$tpl->define_dynamic('mes',			'content');
	$tpl->define_dynamic('mes_item',	'content');

	$tpl->define_dynamic('toplevel',	'content');
	$tpl->define_dynamic('flkvt',		'content');

	$tpl->define_dynamic('kvplan',		'content');
	$tpl->define_dynamic('kvplan_item',	'content');

	$tpl->define_dynamic('kvinfo_1',		'content');
	$tpl->define_dynamic('kvinfo_2',		'content');
	$tpl->define_dynamic('kvinfo',		'content');
	$tpl->define_dynamic('kvroomr',		'content');

	$tpl->define_dynamic('kvstat',		'content');

	$tpl->define_dynamic('kvlog',		'content');
	$tpl->define_dynamic('kvlog_item',	'content');
	$tpl->define_dynamic('kvlog_empty',	'content');
	$tpl->define_dynamic('kvlog_addtime',	'content');
	$tpl->define_dynamic('kvlog_del',	'content');

	$tpl->define_dynamic('vkladka_1',		'content');
	$tpl->define_dynamic('vkladka_2',		'content');
	$tpl->define_dynamic('vkladka_3',		'content');
	$tpl->define_dynamic('vkladka_4',		'content');


	$tpl->define_dynamic('head_obj_undercostr', 'content');
	$tpl->define_dynamic('footer_obj_undercostr', 'content');
	$tpl->define_dynamic('head_obj_complite', 'content');
	$tpl->define_dynamic('footer_obj_complite', 'content');

	$tpl->define_dynamic('grafic_1', 'content');
	$tpl->define_dynamic('grafic_2', 'content');

	$tpl->define_dynamic('rassr_posgk', 'content');

	$tpl->parse_dynamic('kvlog_del', 'DEL');
	$tpl->parse_dynamic('kvlog_addtime', 'ADDTIME');

	$tpl->define_dynamic('rsign_block',		'content');

	$tpl->define_dynamic('info_block',		'content');
	$tpl->define_dynamic('info_block_item',		'content');

	$tpl->define_dynamic('dpk_block',		'content');
	$tpl->define_dynamic('dpk_block_item',		'content');



	$tpl->define_dynamic('kva',		'content');

	$tpl->clear('DEL');
	$tpl->clear('ADDTIME');

	$tpl->clear_href('DEL');
	$tpl->clear_href('ADDTIME');

	$tpl->define_dynamic('command',			'content');
	$tpl->define_dynamic('command_add',		'content'); // �������� ������
	$tpl->define_dynamic('command_num',		'content'); // ��������� �����
	$tpl->define_dynamic('command_unlock',	'content'); // ����������
	$tpl->define_dynamic('command_lock',	'content'); // �� ���������
	$tpl->define_dynamic('command_rsign',	'content'); // �������
	$tpl->define_dynamic('command_rsign_key',	'content'); // �������
	$tpl->define_dynamic('command_rsign_create',	'content'); // �������
	$tpl->define_dynamic('img_teflon',	'content'); // teflon


// �������� ��������
$kv = new kv($kvid);
$kv->get();

//�������� ������
$ob = new obj($kv->obj());
$ob->get();

include_class('kvsort');
$ks = new kvsort($kv->fv('kvsort_id'));
$ks->get();

$kt = new kvtype();
list($ktid, $seid) = $kt->r("
	declare @xid numeric
	select @xid = $kv->id
	select kvtype_id, section_id
	from kv, well w
	where 
		kv.kv_id = @xid and kv.well_id=w.well_id
");
	$kt->get($ktid);


$tpl->assign(array(
	'kvid'			=> $kvid,
	'js_is_tman'	=> ($user_type == 2) ? 'true' : 'false',
	tcost	=> nfd($kv->fv('tcost'))
)); 


//----------------�����������--------- ������


//���������� ����� ������� ����� ����� ���������
//����2 ������ ����1
function date_count_month($date1, $date2){
	//m2 + 12*(�2 - �1) - m1
	$date = date('n', $date2) + 12*(date('Y', $date2) - date('Y', $date1)) - date('n', $date1);

	//� ���������� ����� ���������� 0 ���� ������ ���������
	if(!$date || $date == 0){

		$day = date('j', $date2) - date('j', $date1);
		if($day >= 0){
			$date = 1;
		}
	}
	elseif($date > 0){
		$date +=1;
	}


	return $date;
}



//������� ������������ ������� � �������� ����� ��
//price ���� �������� �� ������� ���� ������(� �� ������ � ������� ������) � ������� ������
//dogk_month ����� ������� �� ��
//all_month ����� ������� �����
//procent ������� ����������
function platej_aftgk($price, $dogk_month, $all_month, $procent){  

	$dogk_month -= 1;
	$all_month -= 1;
	if($dogk_month < 0){	
		$dogk_month = 0;
	}
	if($all_month > 0){

		$aftgk_month  = $all_month - $dogk_month;

		$str1 = "<br>����".$price."<br>";
		$str1 .= "<br>��� �� ��".$dogk_month."<br>";
		$str1 .= "<br>��� ����� ��".$aftgk_month."<br>";
		$str1 .= '
		<table border="1">
			<tr>
				<td>���������</td>
				<td>�����������</td>
				<td>���������</td>
				<td>$i</td>
			</tr>

		';
			$main_sum = 1+ $procent/100;
		 
			for($i=0; $i <= $aftgk_month; $i++){
				$slagaem = pow($main_sum, $i);
		$str1 .= '	<tr>
				<td>'.$slagaem.'</td>';
				if( $i == $aftgk_month){
					$chislit = $price * $slagaem;
					$slagaem = $slagaem * $dogk_month;
				}
				$znam += $slagaem;
		$str1 .= "<td>".$znam."</td>";
		$str1 .= "<td>".$chislit."</td>";
		$str1 .= "<td>".$i."</td>";

		//echo '��������� '.$slagaem."<br />";
		//echo '��������� '.$chislit."<br />";

			}
		$str1 .= "</table><br /><br />";
		$platej = ceil($chislit / $znam);


		//echo $str1;	
	}
	else{
		$platej = 0;
	}

	return $platej;
}


//������� ������������ ������� � �������� �� ��
//price ����� �� ������� ���� ������ � ������� ������
//dogk_mounth_count ���������� ������� �� ��
function platej_dogk($price, $sell_data){

	$platej = $price / ($sell_data['dogk']['month_count'] - 1);

	return $platej;
}



function calc_checker(&$sell_data, $procent){
	
	$price = $sell_data['full_price'];
	$str = '<table>';

	//������ ���� �������� � �������� ����� ��(��������� ��������)
	$sell_data['posgk']['full_price'] = $sell_data['full_price'] - $sell_data['posgk']['first_pay_disc'];
	//����� ��������� �� �� ��� ����� ��������� �� � ����� �� �� (��������� ��������)
	$sell_data['posgk']['summ_dogk'] = $sell_data['posgk']['first_pay'];


	//���������� ������ ��� ���� � ������ �������
	for($i=1; $i <= $sell_data['all_month_count']; $i++){
		//���� �� � ���� ����� ��������� ������ ����������
		$goto_dopprise = false;

		$str .= '<tr>';
		$str .= '<td>'.$price;

		//� ������ ������ ���������� ������
		if($i == 1){
			// � ������ ����� ������ ������ � ������ � �����
			$vichitaemoe = $sell_data['posgk']['first_pay_disc'] + $sell_data['posgk']['first_pay'];
		}
		elseif($sell_data['posgk']['corrective'] && $i <= $sell_data['dogk']['month_count']){
			// ������� ��������� �� ��, ������ �� �������������� ���� ������
			$vichitaemoe = $sell_data['posgk']['mounth_price_do'];			
		}
		else{
			// ����������� ������� ����� ��
			$vichitaemoe = $sell_data['posgk']['mounth_price_aft'];
		}

		$price  -= $vichitaemoe;

		// ��������� ���� �� ��������� �������������� % � ���� ����� �� ��
		if($i <= $sell_data['dogk']['month_count']){
			//������� �� ��
			$sell_data['posgk']['summ_dogk']  += $vichitaemoe;

			if($i == $sell_data['dogk']['month_count']){
				$goto_dopprise = true;
			}

		}
		else{
			//������� ����� ��
			$goto_dopprise = true;
		}
		//�������������� ������� ��� �������� ����������
		if($sell_data['posgk']['need_nadbavka']){
			
			$sell_data['posgk']['full_price'] += $sell_data['posgk']['nadbavka'];
			if($sell_data['posgk']['summ_dogk'] > 0){
				$sell_data['posgk']['summ_dogk']  += $sell_data['posgk']['nadbavka'];
			}
		}


		//������� ��������������� �������� � ��� ���� � ������ ���� � ���� ��������
		if($goto_dopprise){
			$dop_price = $price * ($procent/100);
			if($dop_price < 0){
				$dop_price = 0;
			}
			$sell_data['posgk']['full_price'] += $dop_price;
			$price  += $dop_price;
		}

		$str .= ' + '.$dop_price.'</td>';
		$str .= '<td>'.$vichitaemoe.'</td>';
		$str .= '<td>'.$price.'</td>';
		$str .= '<td>'.$i.'</td>';
		$str .= '</tr>';

	}
	$str .= '</table>';

	//-----------������ �������� ����������� ��������----------------
	//������ �������� �� ����������� ������

	//������� ����������� ����� �� �������� ���� ������ 0 ����� 0
	$sell_data['posgk']['min_full_price'] = ceil($sell_data['full_price']*$sell_data['posgk']['min_ydoroganie']/100);
	
	if($sell_data['posgk']['min_full_price'] > $sell_data['posgk']['full_price']){
		//����� ������ ������������ �����������, ���������
		$nedobor = $sell_data['posgk']['min_full_price'] - $sell_data['posgk']['full_price'];
		$sell_data['posgk']['nadbavka'] = ceil($nedobor / $sell_data['posgk']['min_full_price']);
		$sell_data['posgk']['need_nadbavka'] = true;
		calc_checker(&$sell_data, $procent);
	}


	//��������� ��������� ������� �� �� �������� ��� ���
	if($sell_data['posgk']['must_pay_dogk'] == $sell_data['posgk']['first_pay_proc']){
		//������� �������� ����������� ���������� �������
		$rasshet_ok = true;
	}
	else{
		//��������� ����� �� �� ���������� ��� ���	
		$sell_data['posgk']['must_pay_dogk_price'] = $sell_data['full_price']*$sell_data['posgk']['must_pay_dogk']/100;

		if($sell_data['posgk']['must_pay_dogk_price'] > $sell_data['posgk']['summ_dogk']){
			//��������� ���������� ��� ��� �� ��������� � ����� �� ��
			//������� �� ������� �������� �������� � ������ ��������� �� �� � ����� ��
			$rasshet_ok = false;
		}
		else{
			// ��� ��  
			$rasshet_ok = true;
		}
	}

	
	if($rasshet_ok){
		// �������� ������ ������� ���� ��� ������ ������ �������� ������ �������������� ���� �� ��
		if(!$sell_data['posgk']['mounth_price_do']){
			$sell_data['posgk']['mounth_price_do'] = $sell_data['posgk']['mounth_price_aft'];
		}
		//����� ����������� ��������
		//echo $str;
	}
	else{
		$sell_data['posgk']['need_nadbavka'] = false;
		//�������� ������ ��������
		$sell_data['posgk']['corrective'] = 1;

		//������ �������� �� ��
		if($sell_data['dogk']['month_count'] == 1){
			//���� ���� ����� ��� ������ � ������ ������ � ������ ������
			$sell_data['posgk']['first_pay'] = ceil($sell_data['posgk']['must_pay_dogk_price']/(1 + $sell_data['once_disc']/100));
			$sell_data['posgk']['first_pay_disc'] = $sell_data['posgk']['first_pay']*$sell_data['once_disc']/100;
		}
		else{
			//1 ������ �� ������� � ����������� �� ������� �� ��
			$new_dogk_ostatol = $sell_data['posgk']['must_pay_dogk_price'] - $sell_data['posgk']['first_pay'];
			$sell_data['posgk']['mounth_price_do'] =  platej_dogk($new_dogk_ostatol, $sell_data);		
		}

		//����� ������� ��� �������� ������� ����� ��
		$new_ostatok = $sell_data['posgk']['full_price'] - $sell_data['posgk']['must_pay_dogk_price'] -$sell_data['posgk']['first_pay_disc'];

		$new_month = $sell_data['all_month_count'] - $sell_data['dogk']['month_count'];

		//����������� �� ������ �������� ���������� ������
		if($sell_data['posgk']['first_pay_null']){
			$dop_month = 1;
		}

		$sell_data['posgk']['mounth_price_aft'] = platej_aftgk($new_ostatok, 0, $new_month + $dop_month, $procent);

		//������������� �� ������ ���������� ������� ������ ���
		if($sell_data['posgk']['first_pay_null']){
			$sell_data['posgk']['first_pay'] = 100*$sell_data['posgk']['mounth_price_aft']/(100 + $sell_data['posgk']['first_pay_disc']);	$sell_data['posgk']['first_pay_disc'] = $sell_data['posgk']['first_pay']*$sell_data['once_disc']/100;	
		}
		//������ ����� ��������
		calc_checker(&$sell_data, $procent);


	}
	$sell_data['posgk']['full_price'] = floor ($sell_data['posgk']['full_price']);
	
}

//������ ������� �� ��������
function discont_cleaner($arr1, $arr2){
	if(is_array($arr2)){
		foreach($arr1 as $disc){
			if(array_search($disc['disc_id'], $arr2)){
				$new_disc[$disc['disc_id']] = $disc;
			}
		}
	}
	return $new_disc;
}


//�������� ��� ������ 
//������������ ������ ������ ���������� � ������ ����������� ������
//$disc_all - ������ ��� ��������, $disc_checher - ������ ��� ������ ��������
function disc_disc($disc_all, $disc_checher){
	
	$el_count = count($disc_all);
	if($el_count > 1){

		for($m=0;$m < $el_count-1; $m++){
			$tm_err = '';

			for($k=$m+1;$k < $el_count-1; $k++){
				if($disc_checher[$sell_data['user_discont'][$m]][$sell_data['user_discont'][$k]]){

					$tm_err['d1'] = $sell_data['user_discont'][$m];
					$tm_err['d2'] = $sell_data['user_discont'][$k];
					$error_disc[] = $tm_err;
				}					
			}

			if($tm_err){
				break;
				unset($disc_all[$k]);
				sort($disc_all);

				disc_disc($disc_all, $disc_checher);
			}
		}
	}

	$rezalter['disc_ok'] = $disc_all;
	$rezalter['disc_fail'] = $error_disc;

	return $rezalter;
}

//---���� ���������� ��� ���������---

//��������� ������ ����������� ������������
$obj_id = $ob->id;   //��� ����������� ������ ����������
$kv_cost = $kv->fv('tcost');	//��������� ���� ��������
$kv_type = $ks->fv('code');		//��� ���� ��������� ���������� ������ ����� ������. ������

//�������� �� ������� ����� ����� ������� �� ����������  rare
switch($obj_id){
	case '18000000000000040':
	case '18000000000000067':
	case '18000000000000076':
	case '18000000000000058':
	case '18000000000000038':
	case '18000000000000060':
		$procent = 0.8;
	break;

	case '18000000000000055':
		$procent = 0.7;
	break;

	case '18000000000000147':
		$procent = 0;
	break;

	default:
		$procent = 1;
	break;
}


//������ �� ����������(���� ��� ���., ������������ ������, ������ ��- �����, ������ ����, �� ����, ������� ������)


//���� �������� ���� ������������ ����- ����� ��� ����
if(!$dog_date){
	$dog_date = time();
}
else{
	$dog_date = strtotime($dog_date);
}
		
$today = date('Y-m-d', $dog_date).' 0:00:00';


$obj_regl_list = $d->rrc("
						declare @dat datetime
						select @dat = '{$today}' 
						declare @xid numeric
						select @xid = {$obj_id}
						SELECT 
								regl_gkdt, regl_mdisc, regl_sdisc, regl_pdisc, regl_id, regl_omp, turn_id	
						FROM 
							regl 
						WHERE 
							reglt_id = (
										SELECT 
												reglt_id 
											FROM 
												reglt 
											WHERE 
												reglt_bdt <= @dat AND 
												reglt_edt >= @dat
										) AND 
							obj_id= @xid
						");

$sell_data = $obj_regl_list[0];

$sell_data['dog_date'] = $dog_date;

//������������ ���� ������������ ��
$sell_data['regl_gkdt'] = strtotime($sell_data['regl_gkdt']);

$sell_data['gk_date'] = strtotime($d->cc("select turn_dt from turn where turn_id = {$sell_data['turn_id']}"));

//���� ��������
$sell_data['full_price'] = $kv_cost;

//��������� �������������� ������ 
//���������� ������ � ������� ������
if($kv_type === '000' || $kv_type === '002'){
	//�������� � ������
	$sell_data['once_disc'] = $sell_data['regl_sdisc'];
}
elseif($kv_type === '001' || $kv_type === '003'){
	//���������� ���������
	$sell_data['once_disc'] = $sell_data['regl_pdisc'];
}
if(!$sell_data['once_disc']){
	$sell_data['once_disc'] = $sell_data['regl_omp'];
}
		
//��������� ��� �������������� ������
$sell_data['once_price'] = ($sell_data['full_price'] * (100 - $sell_data['once_disc']))/100;

if(!$sell_data['month_pay_date']){
	$sell_data['month_pay_date'] = $sell_data['dog_date'];
}

//���� �������� (���� ���� ������ 28, ������ ������ ���� 28 �����)
if(date('j', $sell_data['month_pay_date']) > 28 ){
	$sell_data['month_pay_date'] = strtotime(date('Y', $sell_data['dog_date']).'-'.date('m', $sell_data['dog_date']).'-28');
}

//�� ������ ������ ���� ����� �������� JS
if($user_first_pay_sum > $sell_data['full_price']){
	$user_first_pay_sum = $sell_data['full_price'];	
}

//����������� ���� + ���������� ���� ������ ��� ������ ������ ������
$sell_data['first_pay_day'] = $sell_data['dog_date'] + $_NUMREDDAYS * 86400; 
		

//���������� ������� ������������
$sell_data['dogk']['month_count'] = date_count_month($sell_data['first_pay_day'], $sell_data['regl_gkdt']);
if($user_all_month < $sell_data['dogk']['month_count'] && $user_all_month > 0){
	$sell_data['dogk']['month_count'] = $user_all_month;
}

// ���� ������ ��� ��� 
if ($sell_data['gk_date'] <= $sell_data['first_pay_day']){
	$sell_data['sdan'] = true;
}

//��� ��������� ������� ��������� �� ������ ���������
$sell_data['all_rasr'] = $d->rrc("
					declare @xid numeric
					select @xid = {$sell_data['regl_id']}

					SELECT 
						rasr.rasr_id, 
						rasr.rasr_stitle,
						rare.rare_bgkp
					FROM 
						rare, rasr 
					WHERE 
						rare.regl_id= @xid AND
						rare.rasr_id = rasr.rasr_id AND 
						rare.rare_actf =1 
					");

//��� ������ ������� ��������� �� ������
$sell_data['all_discont'] = $d->rrc("
					declare @xid numeric
					select @xid = {$sell_data['regl_id']}

					SELECT 
						disc.disc_id, 
						disc.disc_title,
						disc.disc_v
					FROM 
						disc, dire 
					WHERE 
						dire.regl_id= @xid AND
						disc.disc_id = dire.disc_id AND 
						disc.disc_actf = 1 
					");

$m = 0;
$disc_where = '';
//�������� ���� �� ������������ �����-���� ������
foreach($sell_data['all_discont'] as $tm_disc){
	$tm_var_name = 'user_d_'.$tm_disc['disc_id'];
	if($$tm_var_name){

		if($m != 1){
			$disc_where .= ' OR ';
		}
		$disc_where .= 'disc_id = '. $$tm_var_name;
		$sell_data['user_discont'][] = $tm_disc['disc_id'];
		$m++;
	}
}

//���� ������������ ���� ������ ��������� �� �� ������������
if($disc_where){
	//������ �� ��������� ��������� ��� ������ ������� ���� ������������
	$disc_checher = '';
	//��������� � ������ ����� ���� id ������ ������, ������ ���� id ������ ������
	disc_disc($sell_data['user_discont'], $disc_checher);

}

//-------------�� ��----------------
//������ �� ���� ������� ������� �� ��
if(!$sell_data['sdan']){
//echo $sell_data['regl_id']."<br>";


	if($sell_data['dogk']['month_count'] == 1){
		$sell_data['dogk']['pay_month'] = $sell_data['once_price'];
		$sell_data['dogk']['full_price'] = $sell_data['once_price'];
		$sell_data['dogk']['first_pay_disc'] = $sell_data['full_price']*$sell_data['once_disc']/100;
		$sell_data['dogk']['first_pay'] = $sell_data['once_price'];
		$sell_data['dogk']['first_pay_proc'] = 100;
	}
	else{
		//������ �� ��������� (�������� ������� ������)	
		$rare_first = $d->rrc("
					declare @xid numeric
					select @xid = {$sell_data['regl_id']}

					SELECT 
						top 1 rare_fpp, rasr_id	 
					FROM   
						rare 
					WHERE 
						regl_id = @xid AND 
						(	rare_bgkp = 100 OR
							rasr_id = 1 OR 
							rasr_id = 4 OR 
							rasr_id = 2500000000000034 OR
							rasr_id = 2500000000000053)
					");

		$rare_first = $rare_first[0];

		$sell_data['dogk']['first_pay_proc']= $rare_first['rare_fpp'];
		$sell_data['dogk']['rasr_id']= $rare_first['rasr_id'];

		//������ ��� ������� ���� ���������
		$sell_data['dogk']['discont'] = $d->rrc("
					declare @xid numeric
					select @xid = {$sell_data['dogk']['rasr_id']}

					SELECT 
						disc.disc_id, disc.disc_title 
					FROM 
						dira, disc 
					WHERE 
						dira.rasr_id = @xid AND 
						dira.disc_id = disc.disc_id 
					");

	
		//����� ������� �������
		$sell_data['dogk']['first_pay'] = ceil($sell_data['full_price']* ($sell_data['dogk']['first_pay_proc'])/100);
		//��� JS ����� ����� �������� ����������� ��������
		$sell_data['dogk']['min_first_pay'] = $sell_data['dogk']['first_pay'];
		$sell_data['dogk']['min_first_pay_proc'] = $sell_data['dogk']['first_pay_proc'];
		//��������� ������ ����� ��������� ��������
		if($sell_data['dogk']['first_pay']< $user_first_pay_sum){
			$sell_data['dogk']['first_pay'] = ceil($user_first_pay_sum);
			$sell_data['dogk']['first_pay_proc'] = $user_first_pay_proc;
		}

		//�������� �� ��������� ���� ������� ������
		if(!$sell_data['dogk']['first_pay']){
			//������� ������� ���� ������ �� ����� ��� �������� ������������ ������� �������
			//������� ������ ����� ���� �� ������� �������

			$sell_data['dogk']['pay_month'] = ceil((100*$sell_data['full_price'])/($sell_data['once_disc'] + $sell_data['dogk']['month_count']));
					
			//������� ������ ������� ������� ���� ������ �� 0
			if($sell_data['once_disc']){
				$sell_data['dogk']['first_pay'] = (100*$sell_data['dogk']['pay_month'])/(100 + $sell_data['once_disc']);
				$sell_data['dogk']['first_pay_disc'] = $sell_data['dogk']['first_pay']*$sell_data['once_disc']/100;
			}
			else{
				//������� ���� ������ 0
				$sell_data['dogk']['first_pay'] = $sell_data['dogk']['pay_month'];
				$sell_data['dogk']['first_pay_disc'] = 0;
			}
		}
		else{
			//����� ������ � ������� ������
			$sell_data['dogk']['first_pay_disc'] = $sell_data['dogk']['first_pay']*$sell_data['once_disc']/100;

			//������� � ������ ����� � �������� �� ��
			$sell_data['dogk']['ostatok'] = $sell_data['full_price'] - $sell_data['dogk']['first_pay'] - $sell_data['dogk']['first_pay_disc'];
			$sell_data['dogk']['full_price'] = $sell_data['full_price'] - $sell_data['dogk']['first_pay_disc'];
			//����� ������������ ������� ��� ������ �� ��	
			$sell_data['dogk']['pay_month'] =  ceil(platej_dogk($sell_data['dogk']['ostatok'], $sell_data));				
		}

	}

}

//-------------�� � ����� ��----------------
//������������ ����� �� ���� ������ ��� �������

//������������ ������ ���������
if($user_rasr){
// ������������ ���� ���������, ������ ������, ����� ����
$posgk_rare_ar = $d->ric("
			declare @rid numeric
			select @rid = {$sell_data['regl_id']}

			declare @xid numeric
			select @xid = {$user_rasr}

			SELECT top 1
				 rare.rare_id,
				 rare.rare_fpp, 
				 rare.rare_bgkp, 
				 rare.rare_agkdt,
				 rasr.rasr_maskp,
				 rasr.rasr_id
			FROM   
					rare,rasr 
			WHERE
					rare.rasr_id = rasr.rasr_id AND
					rare.regl_id = @rid AND
					rasr.rasr_id = @xid 
");

}

//��� ��������� ��� ������� �� ��������� ��������� ������������� �����������
if(!$user_rasr || !$posgk_rare_ar){
// ������������ ���� ���������, ������ ������, ����� ����
$posgk_rare_ar = $d->ric("
			declare @xid numeric
			select @xid = {$sell_data['regl_id']}

			SELECT top 1
				 rare.rare_id,
				 rare.rare_fpp, 
				 rare.rare_bgkp, 
				 rare.rare_agkdt,
				 rasr.rasr_maskp,
				 rasr.rasr_id
			FROM   
					rare,rasr 
			WHERE
					rare.rasr_id = rasr.rasr_id AND
					rare.regl_id = @xid AND
					rare.rare_actf = 1  
			order by rare.rare_agkdt DESC

");
}


$posgk_rare = current($posgk_rare_ar);

$sell_data['posgk']['first_pay_proc'] = $posgk_rare['rare_fpp'];
$sell_data['posgk']['must_pay_dogk'] = $posgk_rare['rare_bgkp'];
$sell_data['posgk']['last_date'] = strtotime($posgk_rare['rare_agkdt']);
$sell_data['posgk']['min_ydoroganie'] = $posgk_rare['rasr_maskp'];
$sell_data['posgk']['rasr_id'] = $posgk_rare['rasr_id'];
$sell_data['posgk']['rare_id'] = $posgk_rare['rare_id'];

				

//������ ��� ������� ���� ���������
$sell_data['posgk']['all_discont'] = $d->ri("
					declare @xid numeric
					select @xid = {$sell_data['posgk']['rasr_id']}

					SELECT 
						disc.disc_id
					FROM 
						dira, disc 
					WHERE 
						dira.rasr_id = @xid AND 
						dira.disc_id = disc.disc_id AND
						disc.disc_id = 1
					");

$sell_data['posgk']['real_discont'] = discont_cleaner($sell_data['all_discont'], $sell_data['posgk']['all_discont']);


//����� �������
$sell_data['all_month_count'] = date_count_month($sell_data['first_pay_day'], $sell_data['posgk']['last_date']);
if($user_all_month < $sell_data['all_month_count']  && $user_all_month > 0){
	$sell_data['all_month_count'] = $user_all_month;
}


//����� ������� �������
$sell_data['posgk']['first_pay'] = ceil($sell_data['full_price']*$sell_data['posgk']['first_pay_proc']/100);
//��� JS ����� ����� �������� ����������� ��������
$sell_data['posgk']['min_first_pay'] = $sell_data['posgk']['first_pay'];
$sell_data['posgk']['min_first_pay_proc'] = $sell_data['posgk']['first_pay_proc'];
//��������� ������ ����� ��������� ��������
if($sell_data['posgk']['first_pay']< $user_first_pay_sum){
	$sell_data['posgk']['first_pay'] = ceil($user_first_pay_sum);
	$sell_data['posgk']['first_pay_proc'] = $user_first_pay_proc;
}

//������� �������� 100% ������ ��� ����� ��������� � 1 �����
if($user_all_month == 1 || $user_first_pay_sum == $sell_data['full_price']){
	$sell_data['posgk']['pay_month'] = $sell_data['once_price'];
	$sell_data['posgk']['full_price'] = $sell_data['once_price'];
	$sell_data['posgk']['first_pay_disc'] = $sell_data['full_price']*$sell_data['once_disc']/100;
	$sell_data['all_month_count'] = 1;
	$sell_data['posgk']['first_pay'] = $sell_data['once_price'];
	$sell_data['posgk']['first_pay_proc'] = 100;
}


//����� ������ � ������� ������
$sell_data['posgk']['first_pay_disc'] = $sell_data['posgk']['first_pay']*$sell_data['once_disc']/100;

//������� � ������ ����� � �������� �� ��
$sell_data['posgk']['ostatok'] = $sell_data['full_price'] - $sell_data['posgk']['first_pay_disc'] - $sell_data['posgk']['first_pay'];
		
//������ ����� � ���� ������ ������ , ������ ����������
if($sell_data['dogk']['month_count'] == 0 && $sell_data['dogk']['month_count']){
	$dop_price = $sell_data['posgk']['ostatok'] * ($procent/100);
	$sell_data['posgk']['ostatok']  += $dop_price;
}

//����������� �� ������ �������� ���������� ������
if(!$sell_data['posgk']['first_pay']){
	$dop_month = 1;
	$sell_data['posgk']['first_pay_null'] = true;
}

$sell_data['posgk']['mounth_price_aft'] = platej_aftgk($sell_data['posgk']['ostatok'], $sell_data['dogk']['month_count']+$dop_month,																		$sell_data['all_month_count']+$dop_month, $procent);
//������������� �� ������ ���������� ������� ������ ���
if($sell_data['posgk']['first_pay_null']){
	$sell_data['posgk']['first_pay'] = 100*$sell_data['posgk']['mounth_price_aft']/(100 + $sell_data['posgk']['first_pay_disc']);	$sell_data['posgk']['first_pay_disc'] = $sell_data['posgk']['first_pay']*$sell_data['once_disc']/100;	
}



calc_checker($sell_data, $procent);

__pr($sell_data);  

$tpl->assign(array(
		'ecost' => nfd($sell_data['once_price']),
		'step' => $step,
		'tcost_sum' => $sell_data['full_price']
		));  


if($sell_data['sdan']){
	//$tpl->parse('KVPLAN_item', '.kvplan_item');

	$tpl->parse(COMPUP, 'head_obj_complite');
	$tpl->parse(COMPDOWN, 'footer_obj_complite');
}
else{

	if( $ks->fv('code') === '002'){
		// ������
		$tpl->assign(array(
			'dogkfirst' => nfd($sell_data['dogk']['first_pay']),
			'dogkmonth' => nfd($sell_data['dogk']['pay_month']),
			'dogkcost' => nfd($sell_data['dogk']['full_price'])
		));   
	}
	else{
		//���������� ��������� � ��������
		$tpl->assign(array(
			'dogkfirst' => '� ����������',
			'dogkmonth' => '� ����������',
			'dogkcost' => '� ����������'
		));   
	}	



	$tpl->parse(UNDERUP, 'head_obj_undercostr' );
	$tpl->parse(UNDERDOWN, 'footer_obj_undercostr');
} 

if( $ks->fv('code') === '002'){
	// ������
	$tpl->assign(array(
		'posgkfirst' => nfd($sell_data['posgk']['first_pay']),
		'posgkmonth_do' => nfd($sell_data['posgk']['mounth_price_do']),
		'posgkmonth_pos' => nfd($sell_data['posgk']['mounth_price_aft']),
		'posgkcost' => nfd($sell_data['posgk']['full_price']),
		'underconstract' => ''
	)); 
			

}
else{
	//���������� ��������� � ��������
	$tpl->assign(array(
		'posgkfirst' => '� ����������',
		'posgkmonth_do' => '� ����������',
		'posgkmonth_pos' => '� ����������',
		'posgkcost' => '� ����������'
	)); 

	if(!$step or $step == 1){
		$tpl->assign(array(
			'underconstract' => ''
		)); 			
	}
	else{
		$tpl->assign(array(
			'underconstract' => '� ����������'
		)); 
	}

}
	

//-----------�����������--------------�����



	$katinfo = $kv->cc("
		declare @xid numeric
		select @xid = $kv->id
		select dbo.get_katinfo(@xid, 0) katinfo
	");
	$kvainfo = $kv->cc("
		declare @xid numeric, @xd date
		select @xid = $kv->id, @xd = '".date('Y-m-d')."'
		select dbo.get_kvainfo(@xid, @xd) kvaid
	");

	$section_title = $kv->cc("
		declare @xid numeric
		select @xid = ".$kv->fv('well_id')."
		select section_title
		from
			section, well
		where
			well_id = @xid and
			well.section_id = section.section_id
	");


	$_tcost = $kv->fv('tcost');
	$floor = (int)$kv->fv('floor');
	$idn = $kv->fv('snum');

	$floor_str = "";
	if ($floor < 0) {
		$floor_str = abs($floor) . " - ���������";
	} else if ($floor == 1) {
		$floor_str = "������";
	} else {
		$floor_str = $floor;
	}

	list($kvstat_code, $kvstat_title) = $kv->r("
		declare @xid numeric
		select @xid = ".$kv->fv('kvstat_id')."
		select kvstat_code, kvstat_title from kvstat where kvstat_id=@xid
	");

	// rsign button
	$sql = "
		declare @xid numeric, @kvstatid002 numeric
		select @xid = $kv->id
		select @kvstatid002 = (select kvstat_id from kvstat where kvstat_code='002')

		select count(*)
		from rsign
		where
			rsign_actf = 1 and
			kvlog_id in (
				select kvlog_id
				from kvlog
				where
					kv_id = @xid and
					kvlog_actf = 1 and
					kvlog.kvstat_id = @kvstatid002
			)
	";
	$rsign_status = ($kv->c($sql) > 0) ? true : false;
	$rsign_status_str = $rsign_status ? "����� �������" : "���������";


	// ��� � ���� ����� ��������
	$view_kva		= 1;
	$view_mes		= 1;
	$view_kvlog		= 0;
	$view_kvplan	= 1;
	$view_rsign		= 1;
	$view_info		= 1;

	if ( in_array($user_type, array(1,2,3)) ) {
		$view_kvlog		= 1;
	}

	// ����� ������� ����������, ��������:
	// � ������ ���������� ����� ����������� ��������
	if ($user_mode <> 0) { // ���� � ������ ��������������
	//dbc 2012-01-27
		$donotshowf = false;
		if ($user_type==2) {
			// ���� ��������+ �������� � ������� + � �������� ������� �� �� = �� ����� � ��������. 
			if ($d->c("
				declare @xid numeric, @kvstatid002 numeric
				select @xid = $kv->id
				select @kvstatid002 = (select kvstat_id from kvstat where kvstat_code='002')
			
				select count(*)
				from kvlog
				where
					kv_id = @xid and
					kvlog_actf = 1 and
					kvlog.kvstat_id = @kvstatid002
				")>0) {
				if ($d->c("
					declare @xid numeric, @kvstatid002 numeric
					select @xid = $kv->id
					select @kvstatid002 = (select kvstat_id from kvstat where kvstat_code='002')
				
					select count(*)
					from kvlog
					where
						kv_id = @xid and
						kvlog_actf = 1 and
						kvlog.kvstat_id = @kvstatid002 and
						kvlog.tman_id in (".$auth->auth[tman][in].")
					")<1) {
						$donotshowf = true;
				}
			}
			// ������� � ������� � ��� - �� �� �������
			if ($d->c("
				declare @xid numeric
				select @xid = $kv->id
			
				select count(*)
				from kkv
				where
					kv_id = @xid and kkv_actf=1 and kkv_code='sdi'
			")>0) {
						$donotshowf = true;
			}
		}
		if ($kv->is_lock()) {
			$donotshowf = true;
		}
		if ($user_type == 3) {
			$donotshowf = false;
		}
		if (!in_array($kvstat_code, $kvstat_code_arr)) {
			$donotshowf = true;
		}

		// ���� ��. �� � ������ �������� ��� � ���������� ����� ������ ��������
	//	if (!in_array($kvstat_code, $kvstat_code_arr) || ($kv->is_lock() && $user_type <> 3)) {
		if ($donotshowf) {
			$user_mode = 0;
			$view_kvlog	= 0;

			if ($lock_flag) {
				$lock_flag = send_term($kvid, 'unlock');
			}
		}

	}


	$kvstat_img = array(
		'000'=>'/images/okgb.gif',
		'001'=>'/images/zamokb.gif',
		'002'=>'/images/okrb.gif',
		'003'=>'/images/docb.gif',
		'004'=>'/images/oknb.gif'
	);


	if ($cmd && ($user_mode <> 0)) {

		switch ($cmd) {
			case 'exit':
				$lock_flag = send_term($kvid, 'unlock');
				if ($lock_flag > 0) {
					echo "
	<script type=\"text/javascript\">
		try {
			if (window.opener.document.mhf.refresh) {
				window.opener.document.mhf.submit();
			}
		} catch(e) {}
		window.opener.focus();
		window.close();
	</script>
					";
					page_close();
					exit;
				}
			break;

			case 'kvlog_del':
				if ($kvlogid) {
					location("/chesscell.php?JS=$sess->id&act=bd2&id=$kv->id&klid=$kvlogid");
				}
			break;
			case 'kvlog_addtime':
				if ($kvlogid) {
					location("/chesscell.php?JS=$sess->id&act=addtime&id=$kv->id&klid=$kvlogid");
				}
			break;
			case 'rsign':
				location("/rsign.php?JS=$sess->id&id=$kv->id");
			break;
			case 'rsign_key':
				location("/rsign.php?JS=$sess->id&id=$kv->id&act=get_key");
			break;

			default:
				location("/chesscell.php?JS=$sess->id&act=$cmd&id=$kv->id");
			break;
		}

	}



	// ��������


	list($kvsort_code, $kvsort_title, $kvsort_def) = $d->r("
		declare @xid numeric
		select @xid = ".$kv->fv('kvsort_id')."
		select kvsort_code, kvsort_title, kvsort_def from kvsort where kvsort_id = @xid
	");


	$is_golden=false;
	$page_print="chsp.php";
	if($ob->id==='18000000000000043'){//Golden Line (��������)
		$page_print="plrp_gl.php";
		$is_golden=true;
	}
	if($ob->id==='18000000000000055'){//Golden Line (��������)-�������
	//	$page_print="plrp_gl.php";
	}

	// ���������
	include_class('oo');
	$oo = new oo();
	$oo->set_id($ob->id);
	$a = $oo->up_array('obj_id');
	$obj_arr = array();
	$obj = new obj();
	foreach ($a as $key => $val) {
		$obj->get($val);
		$obj_arr[] = $obj->fv('title');
	}
	unset($oo,$obj);

	if ($user_mode) {
		$su = new suser();
		$suser_title = $su->sname($auth->auth['uid']);
		$carr = array_merge($obj_arr,array('���: '.$kt->fv('title'), 'IDN: '.$kv->fv('snum')));
		$cstr = implode(' - ', $carr);

		$mes[] = '�� ��������� <b>� ������ ��������������</b> (<b>!</b>). '.$cstr.' ������������� ������������� - '.$suser_title;
		$mes[] = '�� ��������(<b>!</b>) ����� ��������� ������ ������ ������ &rarr; <span class="button-exit">{button_exit_title}</span>';
	}





	//if ($_REQUEST['refresh'] && $user_type == 3) {
	if ($_REQUEST['refresh']) {
		$tpl->parse(JS_REFRESH, 'js_refresh');
	}

	// ��������� ������
	if (count($err)) {
		foreach ($err as $e) {
			$tpl->assign(array('err_message' => $e));
			$tpl->parse(ERR_ITEM, '.err_item');
		}
		$tpl->parse(ERR, 'err');
	}
	else {
		if (count($mes)) {
			foreach ($mes as $m) {
				$tpl->assign(array('mes_message' => $m));
				$tpl->parse(MES_ITEM, '.mes_item');
			}
			$tpl->parse(MES, 'mes');
		}
	}


	// �������
	$kvlog_count_flag		= 0; // ����� ��������
	$tman_kvlog_count_flag	= 0; // ���� �� ������ � tman
	$view_unlock_button		= 0; // ���������� ��� ��� ������ "����������"
	$view_rsign_button		= 1; //$rsign_status ? 1 : 0; // ���������� ��� ��� ������ "�������"
	$kv_is_teflon			= false;//�������� ����� �������������� �������� ���� "teflon"

	$kv_is_teflon=$kv->cc("
		declare @xid numeric, @xstr varchar(10)
		select @xid = $kv->id, @xstr = 'teflon'
		select count(*) 
		from kkv 
		where 
			kkv_code=@xstr and 
			kkv_actf=1 and 
			kv_id=@xid
	");
	if ($kv_is_teflon) {
		$tpl->parse('img_teflon_', 'img_teflon');
	}

	$sql = "
		declare @xid numeric, @kvstatid002 numeric
		select @xid = $kv->id
		select @kvstatid002 = (select kvstat_id from kvstat where kvstat_code='002')

		select kvlog_id
		from kvlog
		where
			kv_id = @xid and
			kvlog_actf = 1 and
			kvlog.kvstat_id = @kvstatid002
	";
	if (($kid = $kv->cc($sql))) {

		include_class('kvlog');
		$kvlog = new kvlog($kid);
		$kvlog->get();

		if (($user_type == 2) && ($tmid !== $kvlog->fv('tman_id'))) { // for manager  ���������� ��� ��� ������ "�������"
			$view_rsign_button = 0;
		}
	}


	$is_ipoteka_flag		= 0;

	if ($view_kvlog && ($user_type > 0)) {
		// ����.������
		if ($view_kva) {
			$__dt = date('Y-m-d');
			$kvaf = $d->c("
				select dbo.get_kvainfo($kv->id,'$__dt')
			");
			if ($kvaf) {
				list($kva_def, $kva_img) = $d->r("
					declare @xid numeric, @xd date
					select @xid = $kv->id, @xd = '$__dt'
					select
						kva_def, kva_img
					from kva, kvaa
					where
						kvaa.kv_id = @xid and
						kvaa.kva_id = kva.kva_id and
						kva.kva_actf = 1 and
						@xd >= kva.kva_fdt and datediff(day, @xd, kva.kva_tdt) >= 0
				");
				$tpl->assign(array(
						kva_title	=> $kva_def,
						kva_img		=> $kva_img,
						kv_id		=> $kv->id,
				));
				$tpl->parse('KVA', 'kva');
			}
		}


		// ������ �������� - ������
		$tpl->assign(array('kvstat_img'	=> $kvstat_img[$kvstat_code]));
		if ($user_type == 2) {
			$tpl->parse(KVLOG_STATUS_MANAGER, 'kvlog_status_manager');
		} else {
			$tpl->parse(KVLOG_STATUS_OTHER, 'kvlog_status_other');
		}

		$sql = "	select kvlog.*, tplace_code, tplace_title
					from kvlog, kvstat, tplace
					where
						kv_id = $kv->id and
						kvlog_actf = 1 and
						kvlog.tplace_id = tplace.tplace_id and
						kvlog.kvstat_id = kvstat.kvstat_id and
						kvstat_code != '000'
						order by kvlog_tdt
				";
		if ($rar = $ob->ric("
					declare @xid numeric, @kvstat000 numeric
					select @xid = $kv->id, @kvstat000 = (select kvstat_id from kvstat where kvstat_code='000')

					select
						kvlog.kvlog_id,
						kvlog.kvlog_fdt,
						kvlog.kvlog_tdt,
						kvlog.kvlog_def,
						kvlog.kvlog_dp,
						kvlog.tman_id,
						kvlog.tplace_id,
						tplace_code,
						tplace_title
					from kvlog, tplace
					where
						kv_id = @xid and
						kvlog_actf = 1 and
						kvlog.tplace_id = tplace.tplace_id and
						kvlog.kvstat_id != @kvstat000
					order by kvlog_tdt
	")) {



			// ���� ������ ��������  �� �������
			// ������ 2012-01-25
			if($kvstat_code!='003'){


			
				$kvlog_count_flag = count($rar);
				
				include_class('kvlog');
				include_class('tman');
				
				$tm = new tman();

				foreach ($rar as $k => $v) {
					$kvlog_del_flag = 0;
					$kvlog_addtime_flag = 0;
					$kvlog_tman_id = (string)$v['tman_id'];
					
					$tm->id = $kvlog_tman_id;
					$tman_sname = $tm->sname();
					
					$klid = $v['kvlog_id'];
					$tplaceid = $v['tplace_id'];
					
					if (($user_mode > 0 ) && !$rsign_status) {
						switch ($user_type) {
							case 2:
								if ($tmid === $kvlog_tman_id) {
									$tman_kvlog_count_flag = 1;
									
									// change 2010-10-27 rdk 
									// $kvlog_del_flag = 1; // - default value
									$kvlog_del_flag = 0;
								}
								if ($kvlog_count_flag == 1 && $kvlog_del_flag != 0) {
									$view_unlock_button = 1;
								}
							break;
							case 3:
								$kvlog_del_flag = 1;
								$kvlog_addtime_flag = 1;
								$view_unlock_button = 1;
							break;
						}
					}


					if ($kvlog_del_flag && $kvlog_count_flag > 1) {
						$tpl->assign(array('klid' => $klid));
						$tpl->parse('DEL', 'kvlog_del');
					} else {
						$tpl->assign(array('DEL'=>''));
					}
					
					if ($kvlog_addtime_flag) {
						$tpl->assign(array('klid' => $klid));
						$tpl->parse('ADDTIME', 'kvlog_addtime');
					} else {
						$tpl->assign(array('ADDTIME'=>''));
					}
					
					$is_ipoteka_flag = $kv->c("
						declare @xid numeric
						select @xid = $klid
						select count(*) from ipoteka where kvlog_id = @xid
					");

					$bank_title = '';
					$kvlog_item_class = '';
					if ($is_ipoteka_flag) {
						//$kvlog_item_class = 'ipoteka';
						$bank_title = $kv->cc("
							declare @xid numeric
							select @xid = $klid
							select bacc_title
							from ipoteka, bacc
							where
								kvlog_id = @xid and
								ipoteka.bacc_id = bacc.bacc_id
						");
					}			
					$tpl->assign(array(
						'klid'		=> $klid,
						'kvlog_item_class' => $kvlog_item_class,
						'tman'      => $tman_sname,
						'kvlog_fdt' => df($v['kvlog_fdt']),
						'kvlog_tdt' => df($v['kvlog_tdt']),
						'tplace_code'	=> $v['tplace_code'],
						'tplace_title'	=> $v['tplace_title'],
						'def'		=> $v['kvlog_def'] != '-' ? htmlspecialchars($v['kvlog_def']) : '',
						'dp'		=> $v['kvlog_dp'],
						
						'bank_title' => $bank_title ? '<img title="�������" style="margin: 0 10px;" src="/images/info.gif" border="0" align="middle" />' . '����: ' . $bank_title . ' ' : '',
					));
					$tpl->parse('KVLOG_ITEM', '.kvlog_item');
					
				}
			}
		} else {
			$tpl->parse('KVLOG_EMPTY', 'kvlog_empty');
		}

		$tpl->assign(array(
					'katinfo'		=> $katinfo,
			));
		$tpl->parse('KVLOG', 'kvlog');
	}


	$reddisableflag = (
		$kv->c("
			declare @xid numeric
			select @xid = $kv->id
			select count(*) 
			from kv, well, selock
			where
				kv.kv_id = @xid and
				kv.well_id = well.well_id and
				well.section_id = selock.section_id
	") > 0) ? true : false;
		
	if ($user_mode && ($user_type <> 0)) {
		switch ($user_type) {
			case 2: // manager
				switch ($kvstat_code) {
					case '000': // ��������
						if ($kvlog_count_flag < 2 and !$kv_is_teflon) {
							$tpl->parse('COMMAND_ADD', 'command_add');
						}
					break;
					case '001': // ������
						if ($kvlog_count_flag < 2 && !$tman_kvlog_count_flag && !$is_ipoteka_flag and !$kv_is_teflon) {
							$tpl->parse('COMMAND_ADD', 'command_add');
						}
						if ($view_unlock_button) {
							$tpl->parse('COMMAND_UNLOCK', 'command_unlock');
						}
					break;
					case '002':
						$view_unlock_button = 0;
						if ($view_unlock_button) {
							$tpl->parse('COMMAND_UNLOCK', 'command_unlock');
						}
						if ($view_rsign_button) {
							if ($rsign_status) {
								//$tpl->parse('COMMAND_RSIGN_KEY', 'command_rsign_key');
							}
							else {
								$tpl->assign(array('rsign_status_str' => $rsign_status_str));
								$tpl->parse('COMMAND_RSIGN_CREATE', 'command_rsign_create');
							}
							$tpl->parse('COMMAND_RSIGN', 'command_rsign');
						}
					break;
				}
			break;
			case 3: // chess
	/*
				if (is_admin()) {
					__pr(array($kvstat_code,$reddisableflag));
				}
	*/
				switch ($kvstat_code) {
					case '000':
						if ($reddisableflag) {
						}
						else {
							$tpl->parse('COMMAND_NUM', 'command_num');
						}
						$tpl->parse('COMMAND_LOCK', 'command_lock');
						if ($kvlog_count_flag < 2) {
							$tpl->parse('COMMAND_ADD', 'command_add');
						}
					break;
					case '001': // ������
						if ($reddisableflag) {
						}
						else {
							$tpl->parse('COMMAND_NUM', 'command_num');
						}
						$tpl->parse('COMMAND_UNLOCK', 'command_unlock');
						if ($kvlog_count_flag < 2) {
							$tpl->parse('COMMAND_ADD', 'command_add');
						}
					break;
					case '002':
						if ($view_unlock_button) {
							$tpl->parse('COMMAND_UNLOCK', 'command_unlock');
						}
						if ($view_rsign_button) {
							if ($rsign_status) {
								$tpl->parse('COMMAND_RSIGN_KEY', 'command_rsign_key');
							}
							$tpl->assign(array('rsign_status_str' => $rsign_status_str));
							$tpl->parse('COMMAND_RSIGN_CREATE', 'command_rsign_create');
							$tpl->parse('COMMAND_RSIGN', 'command_rsign');
						}
					break;
					case '004':
						$tpl->parse('COMMAND_UNLOCK', 'command_unlock');
					break;
				}
			break;
		}

		$tpl->parse(COMMAND, 'command');
	}


	// rsign block
	if ($view_rsign && $rsign_status) {
		include_class('rsign');
		include_class('tman');
		
		$rsignid = $kv->cc("
			declare @xid numeric, @kvstat002 numeric
			select @xid = $kv->id
			select @kvstat002 = (select kvstat_id from kvstat where kvstat_code='002')
			select rsign_id
			from rsign
			where
				rsign_actf = 1 and
				kvlog_id = (
					select kvlog_id
					from kvlog
					where
						kv_id = @xid and
						kvlog_actf = 1 and
						kvlog.kvstat_id = @kvstat002
				)
		");

		$rs = new rsign($rsignid);
		$rs->get();
		$tplace_title = $rs->cc("
			declare @xid numeric
			select @xid = ".$rs->fv('tman_id')."
			select t.tplace_title
			from tmtp, tplace t
			where
				tmtp.tplace_id = t.tplace_id and
				tmtp.tman_id = @xid
		");
		$tm = new tman($rs->fv('tman_id'));
		$tman_title = $tm->sname();
		$kl = new kvlog($rs->fv('kvlog_id'));
		$kl->get();
		$tpl->assign(array(
			tplace_title	=> $tplace_title,
			tman_title		=> $tman_title,
			dp				=> ($kl->fv('dp') <> '-') ? ' ����������: ' . $kl->fv('dp') : '',
			dognum			=> $kl->fv('dn'),
			kvinfo			=> $kv->get_code(),
			rsign_cdt		=> date('Y-m-d H:i', strtotime($rs->fv('cdt'))),
			rsign_suser		=> $rs->fv('suser_id'),
		));
		$tpl->parse('RSIGN_BLOCK', 'rsign_block');
	}

	if ($view_info) {
		$info_arr = array();
		if ($kv->c("
			declare @xid numeric
			select @xid = $kv->id
			select count(*) from kvown where kv_id = @xid
		")) {
			$info_arr[] = array(
				text	=> '��������� ��:  ',
				val		=> $kv->cc("
								declare @xid numeric
								select @xid = $kv->id
								select zk_title from kvown, zk where kvown.zk_id = zk.zk_id and kv_id = @xid
							")
			);
		}

		$today = date('Y-m-d');
		list ($reglt_title, $regl_def) = $kv->r("
			declare @xid numeric, @xd date
			select @xid = $ob->id, @xd = '$today'
			select reglt_title, regl_def
			from regl, reglt
			where
				reglt_edt >= @xd and reglt_bdt <= @xd and
				reglt.reglt_id = regl.reglt_id and
				reglt.reglt_actf = 1 and
				regl.regl_actf = 1 and
				regl.obj_id = @xid
		");
		if ($regl_def !== '-') {
			$info_arr[] = array(
				text	=> '����������� �������: ',
				val		=> $regl_def . '(' . $reglt_title . ')',
			);
		}
		if ($kv->c("
			declare @xid numeric
			select @xid = $kv->id
			select count(*) from kvi where kv_id = @xid
		")) {
			$info_arr[] = array(
				text	=> '�������� �������',
				val		=> '',
			);
		}
		if (count($info_arr)) {
			foreach ($info_arr as $v) {
				$tpl->assign(array(
					info_text	=> $v[text],	
					info_val	=> $v[val],	
				));
				$tpl->parse('INFO_BLOCK_ITEM', '.info_block_item');
			}
			$tpl->parse('INFO_BLOCK', 'info_block');
		}
	}

	if ($view_dpk) {
		$sql = "
			select
				kka_title, kka_vt, kkv_vdt, kkv_vnum, kkv_vstr
			from
				kkv, kka
			where
				kkv_actf = 1 and
				kv_id = {$kv->id} and
				kkv.kka_id = kka.kka_id
		";
		$dpk = $kv->rrc($sql);
		if (count($dpk) > 0) {
			foreach ($dpk as $v) {
				$tpl->assign(array(
					'dpk_text'	=> $v['kka_title'],	
					'dpk_val'	=> $v['kka_vt'] == 'vdt'? df($v['kkv_'.$v['kka_vt']]) : $v['kkv_'.$v['kka_vt']],	
				));
				$tpl->parse('DPK_BLOCK_ITEM', '.dpk_block_item');
			}
			$tpl->parse('DPK_BLOCK', 'dpk_block');
		}
	}

 	$tpl->assign(array(
		'kvstat_title'	=> $kvstat_title	
			
	));


switch ($step) {
	case '1':
	default :
	$flid = $kt->cc("
		declare @xid numeric
		select @xid = $kt->id
		select file_id 
		from flkvt
		where
			flkvt_actf = 1 and
			kvtype_id = @xid
	");
	if (strval($flid) !== '') {
		$tpl->assign(array(
			'fileid' => $flid
		));
		$tpl->parse('RFLKVT', 'flkvt');
	}
	if ($view_kvplan) { // ���� ��������
		$ar = $kt->rr("
			declare @xid numeric
			select @xid = $kt->id
			select file.file_id, file_title
			from flkvp, file
			where
				flkvp.kvtype_id = @xid and
				flkvp_actf = 1 and
				flkvp.file_id = file.file_id
		");
		if (count($ar)) {
			foreach ($ar as $key => $val) {
				$tpl->assign(array(
					'file_id'		=> $val[0],
					'file_title'	=> $val[1],
				));
				$tpl->parse('KVPLAN_item', '.kvplan_item');
			}
		}
		$tpl->parse('KVPLAN', 'kvplan');
	}
	//���������
	$def_fields = array();
	$tsq0 = $psq = $gsq = $bsq = $rsq = 0;
	$ar = $kv->rrc("
		declare @xid numeric
		select @xid = $kvid
		select 
			kvroom_id,
			kvrtype_title,	
			kvrtype_zf,		
			kvrtype_tf,		
			kvroom_num,
			kvroom_sq,
			kvrtype_koof,
			kvroom_gsq,
			kvroom_def
		from kvroom, kvrtype
		where
			kv_id = @xid and
			kvroom.kvrtype_id = kvrtype.kvrtype_id
		order by kvroom_num
	");
	$kvroom_sqtot=0;
	$kvroom_sqptot=0;
	$gsqtot=0;
	foreach ($ar as $key => $val) {
		if($val[kvroom_def]!="-" && $val[kvroom_def]!="" )
		{
			$def_fields[]=$val[kvroom_def];
		}
		$tpl->assign(array(
			kvroom_id		=> $val[kvroom_id],
			kvroom_num		=> $val[kvroom_num],
			kvrtype_title	=> $val[kvrtype_title],
			kvrtype_zf		=> $val[kvrtype_zf]?'�����':'&nbsp;',
			kvroom_sq		=> nfd($val[kvroom_sq]),
			kvroom_sqp		=> nfd($val[kvroom_sq]*$val[kvrtype_koof]),
			kvroom_gsq		=> $val[kvroom_gsq]>0?nfd($val[kvroom_gsq]):'&nbsp;'
		));
		$tpl->parse(RKVROOMR,'.kvroomr');
		$gsq += $val[kvroom_gsq];
		$psq += $val[kvrtype_zf]?$val[kvroom_sq]:0;
		$bsq += $val[kvrtype_tf]?$val[kvroom_sq]:0;
		$rsq += nf($val[kvroom_sq]*$val[kvrtype_koof]);
		$kvroom_sqtot+=$val[kvroom_sq];
		$kvroom_sqptot+=$val[kvroom_sq]*$val[kvrtype_koof];
		$gsqtot+=$val[kvroom_gsq];
	}
	$tpl->assign(array(
		'kvroom_sqtot'		=> nfd($kvroom_sqtot),
		'kvroom_sqptot'		=> nfd($kvroom_sqptot),
		'gsqtot'			=> nf($gsqtot) >0?nfd($gsqtot):'&nbsp;',
	));
	if(is_array($def_fields))$def_fields=implode(" <br> ", $def_fields);

	$tsqq = nf($kv->fv('tsq') - $rsq);
	if ($tsqq > 0) {
		$tsqs = '<b><font color="blue">'.nfd($tsqq).'</font></b>';
	}
	elseif( $tsqq <0) {
		$tsqs = '<b><font color="red">'.nfd($tsqq).'</font></b>';
	}
	else {
		$tsqs = '&nbsp;';
	}
	$curs = getcurs(date('Y-m-d'));
	$curs_eu = getcurs(date('Y-m-d'), 0, 3);

	$allinfo = '����� ����������';

	// add 2011-12-13 rdk
	$is_studio = $kv->is_studio();
	$str_kv="��������";
	switch ($ks->fv('code')) {
		case '000':
			$komnat=$kt->fv('rnum');		
			$rnum_title = $kt->fv('rnum').' ��������� ��������';
			if ($is_studio) {
				$rnum_title = '������';
			}
			$allinfo = $rnum_title . ($kv->fv('pibf')==1? ' � '.$kv->fv('num'): ' IDN '.$kv->fv('snum'));
			if (strval($kt->fv('obj_id')) === '18000000000000043') {
				if ($kt->fv('rnum') == 0) {
					$allinfo = '������';
				}
				else {
					$allinfo = $kt->fv('rnum').' �������� �����������'. ' � '.$kt->fv('title');
				}
			}
		break;
		case '001':
			$allinfo = '���������� ��������� '.$kt->fv('title') ;
			$display='style="display:none;"';
			$str_kv="����������� ���������";
		break;
		case '002':
			$allinfo = '����������� � '.$kv->fv('num').' '.$kt->fv('title');
			if (strval($kt->fv('obj_id')) === '18000000000000055') {// Golden Line (��������)-�������
				$curs=$curs_eu / 100 * 101.5;
			}
		break;
		case '003':
			$allinfo = '������������ ������������ '.$kt->fv('title');
		break;
		case '004':
			$allinfo = $ks->fv('title');
		break;
		default:
		break;
	}


	if(!$is_golden){
		$tpl->assign(array(
			cost	=> nfd($kv->fv('cost')),
			tcostr	=> nfd($kv->fv('tcost')*$curs/1000),
			tsq		=> nfd($kv->fv('tsq')).($kv->fv('pibf')==1?' <font color=red>�� ���</font>':''),
			psq		=> nfd($psq),
			gsq		=> nfd($gsq),
			bsq		=> nfd($bsq),
			rsq		=> nfd($rsq),
			tsq0	=> $tsqs,
			allinfo => $allinfo,
			def_fields => $def_fields.($kt->fv('def')=='-'?'':'<br>'.$kt->fv('def'))
		));

		$tpl->parse(KVINFO_1, 'kvinfo_1');
		$tpl->parse(KVINFO, 'kvinfo');
	}
	else{
		$kv_type = ($kt->fv('rnum') > 0 ? $kt->fv('rnum') . " - ��������" : "������");
		$curs_date = date('Y-m-d');
		$curs = getcurs($curs_date, 0, 3);

		$tpl->assign(array(
			'curs_date'		=> date('d.m.Y',strtotime($curs_date)),
			'curs'			=> nfd($curs,4),
			'tcost'			=> nfd($_tcost), // euro
			'tcostr'		=> nfd(($_tcost * $curs) / 100 * 101.5), // rur
			'obj_id'		=> $ob->id,
			'kvtype_tsq'	=> nfd($kt->fv('tsq')),
			'kvtype_psq'	=> nfd($kt->fv('psq')),
			'kvtype_ksq'	=> nfd($kt->fv('ksq')),
			'kvtype_title'	=> htmlspecialchars($kt->fv("title")) . '. '. $section_title,
			'floor_str'		=> $floor_str,
			'kv_type'		=> $kv_type,
			'def_fields'	=> $def_fields.($kt->fv('def')=='-'?'':'<br>'.$kt->fv('def'))
		));
		$tpl->parse(KVINFO_2, 'kvinfo_2');
		$tpl->parse(KVINFO, 'kvinfo');
	}
	if($kt->fv('axis')!='-'){
		$kvtype_axis = '
			<tr>
			<td class="bb">� ����:</td>
			<td class="bb" align="right"><b>'.$kt->fv('axis').'</b></td>
			</tr>
		';
	}
	else{
	}
	$tpl->assign(array(
		'str_kv'		=> $str_kv,
		'display'		=> $display,
		'komnat'		=> $komnat,
		'obj_id'		=> $ob->id,
		'obj_title'		=> htmlspecialchars($ob->fv("title")),
		'kvtype_id'		=> $kt->id,
		'kvtype_title'	=> $kt->fv('title'),
		'kv_snum'     	=> $kv->fv('snum')=='0'?'&nbsp;':$kv->fv('snum'),
		'kv_num'     	=> $kv->fv('num')=='-'?'&nbsp;':$kv->fv('num'),
		'kv_floor'     	=> $floor,
		'kvstat_class'	=> 'ks'.$kvstat_code,
		
		'kvstat_code'	=> $kvstat_code,

		'kvtype_axis'	=> $kvtype_axis,
		'page_print'	=> $page_print,
	));


	$tpl->parse(VKL1, 'vkladka_1');
		$activ_s1 = ' is_active';
	break;
	case '2':

		

		if( $ks->fv('code') === '002'){
			// ������
			$tpl->parse(VKL2, 'vkladka_2');	
		}
		$activ_s2 = ' is_active';
	break;
	case '3':

		$tpl->assign(array(
			'dogk_firstpay_min'			=> $sell_data['dogk']['min_first_pay'],
			'dogk_firstpay_proc_min'	=> $sell_data['dogk']['min_first_pay_proc'],
			'dogk_firstpay'				=> $sell_data['dogk']['first_pay'],
			'dogk_firstpay_proc'		=> $sell_data['dogk']['first_pay_proc'],
			'dogk_firstpay_disc'		=> $sell_data['dogk']['first_pay_disc'],
			'dogk_cont_mon'				=> $sell_data['dogk']['month_count']
		));

		//����� ����������� ������� �� ��
		for ($i =1; $i <= $sell_data['dogk']['month_count']; $i++) {
			if($i == 1){
				$summ = $sell_data['dogk']['first_pay'];
				$ddate = date('Y-m-d', $sell_data['first_pay_day']);
				$temp_checker_summ = $sell_data['full_price'] - $sell_data['dogk']['first_pay'] - $sell_data['dogk']['first_pay_disc'];
			}
			else{
				$summ = $sell_data['dogk']['pay_month'];
				$temp_checker_summ -= $sell_data['dogk']['pay_month'];
				$sell_data['month_pay_date'] = strtotime("+1 month" ,  $sell_data['month_pay_date']);
				$ddate = date('Y-m-d', $sell_data['month_pay_date']);

				//������������� ���������� ������� �� ������� ������������ � + ��� -
				if($i == $sell_data['dogk']['month_count']){
					if($temp_checker_summ > 0 || $temp_checker_summ < 0){
						$summ += $temp_checker_summ;	
					}
				}
			}

			$tpl->assign(array(
				'gr_num'		=> $i,
				'gr_day'		=> $ddate,
				'gr_sum'	=> $summ,
			));
			$tpl->parse('GRAF_item1', '.grafic_1');
		}

		if( $ks->fv('code') === '002'){
			// ������
			$tpl->parse(VKL3, 'vkladka_3');
		}
		
		$activ_s3 = ' is_active';
	break;
	case '4':

		$tpl->assign(array(
			'posgk_firstpay_min'		=> $sell_data['posgk']['min_first_pay'],
			'posgk_firstpay_proc_min'	=> $sell_data['posgk']['min_first_pay_proc'],
			'posgk_cont_mon'			=> $sell_data['all_month_count'],
			'posgk_first_disc'			=> $sell_data['posgk']['first_pay_disc'],
			'posgk_f_pay_sum'			=> $sell_data['posgk']['first_pay'],
			'posgk_f_pay_porc'			=> $sell_data['posgk']['first_pay_proc']
		));

		foreach($sell_data['all_rasr'] as $rasr_data){
			//����� ������ ��������� �������� �� ��
			if($rasr_data['rare_bgkp'] != 100){
				//���� ���� ���������
				if($sell_data['posgk']['rasr_id'] === $rasr_data['rasr_id']){
					$rasr_selected = 'selected';
				}
				else{
					$rasr_selected = '';
				}
				
				$tpl->assign(array(
					'rasr_value'		=> $rasr_data['rasr_id'],
					'rasr_selected'		=> $rasr_selected,
					'rasr_title'		=> $rasr_data['rasr_stitle']
				));
				$tpl->parse('RASSR_POSGK', '.rassr_posgk');
			}
		}


		for ($i =1; $i <= $sell_data['all_month_count']; $i++) {
			if($i == 1){
				$summ = $sell_data['posgk']['first_pay'];
				$ddate = date('Y-m-d', $sell_data['first_pay_day']);
				$temp_checker_summ = $sell_data['full_price'] - $sell_data['posgk']['first_pay'] - $sell_data['posgk']['first_pay_disc'];
			}
			elseif($i <= $sell_data['dogk']['month_count']){

				$summ = $sell_data['posgk']['mounth_price_do'];
				//������� ��� ����������
				if($sell_data['posgk']['need_nadbavka']){
					$summ += $sell_data['posgk']['nadbavka'];
				}

				$sell_data['month_pay_date'] = strtotime("+1 month" ,  $sell_data['month_pay_date']);
				$ddate = date('Y-m-d', $sell_data['month_pay_date']);
				$temp_checker_summ -= $sell_data['posgk']['mounth_price_do'];
			
			}
			else{
				$summ = $sell_data['posgk']['mounth_price_aft'];
				$temp_dop_price = $temp_checker_summ*$procent/100;
				$sell_data['month_pay_date'] = strtotime("+1 month" ,  $sell_data['month_pay_date']);
				$ddate = date('Y-m-d', $sell_data['month_pay_date']);
				$temp_checker_summ = $temp_checker_summ - $sell_data['posgk']['mounth_price_aft'] + $temp_dop_price ;
				
				//������� ��� ����������
				if($sell_data['posgk']['need_nadbavka']){
					$summ += $sell_data['posgk']['nadbavka'];
				}

				//������������� ���������� ������� �� ������� ������������ � + ��� -
				if($i == $sell_data['all_month_count']){
					if($temp_checker_summ > 0 || $temp_checker_summ < 0){
						$summ = ceil($summ + $temp_checker_summ); 	
					}
				}

			}

			$tpl->assign(array(
				'gr_num'		=> $i,
				'gr_day'		=> $ddate,
				'gr_sum'		=> $summ,
			));
			$tpl->parse('GRAF_item2', '.grafic_2');

			//������� �� ������ ���� ��� ��� ����� ��������� �� ����� ����� �����
			if( $temp_checker_summ <= 0){
				break;
			}
		}

		if( $ks->fv('code') === '002'){
			// ������
			$tpl->parse(VKL4, 'vkladka_4');
		}
		$activ_s4 = ' is_active';
	break;
}

	$tpl->assign(array(
		'st1_activ'		=> $activ_s1,
		'st2_activ'		=> $activ_s2,
		'st3_activ'		=> $activ_s3,
		'st4_activ'		=> $activ_s4,
		'dog_date'		=> date('Y-m-d', $sell_data['dog_date']),
		'once_day'		=> date('Y-m-d', $sell_data['first_pay_day']),

		'JS'			=> $sess->id,
		'button_exit_title'	=> '��������� ������'
	));

$obj_info = array();
if ($kvsort_code == '000') {
	$obj_info[] = '����: '.$kv->fv('floor');
}
$obj_info[] = '���: '.$kt->fv('title');
$obj_info[] = 'IDN: '.$kv->fv('snum');
$tab = '&nbsp;&nbsp;<img src="/images/view.gif" border="0">&nbsp;&nbsp;';
$carr = array_merge(array($kvsort_def),$obj_arr, $obj_info);
$cstr = implode($tab, $carr);

$tpl->parse(CONTENT, 'content');
$content["_content"]	= $tpl->fetch(CONTENT);
$content["_title"]		= $kt->fv('title');
$content["_rp_menu"]	= '';
$content["ctitle"]		= $cstr.' '.$katinfo.($kvainfo?'<img src="/images/a/star.gif" title="�����" onclick="kvaccii(\''.strval($kv->id).'\');" style="cursor:hand" >':'');
print_page('chs', 'reports', $content, 'current');
page_close();

function send_term($kvid='', $term='') { 
	return(kv_send_term($kvid, $term));
}





?>