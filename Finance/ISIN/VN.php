<?php

class Finance_ISIN_VN extends Finance_ISIN
{
    function getLocationISIN($stockcode)
    {
        if(isset($this->map[$stockcode])) {
            return $this->map[$stockcode];
        }

        $ar = explode('.', $stockcode);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.hsx.vn/Areas/Desktop/Web/Searchh?q={$ar[0]}&_search=false&rows=30&page=1&sidx=id&sord=desc");   
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $str = curl_exec($ch);
        curl_close($ch);

        $json = json_decode($str, true);

        $jsonError = json_last_error();

        if($jsonError != JSON_ERROR_NONE) {
            return false;
        }

        $a = false;

        foreach($json['rows'] as $record) {
            if(trim($record['cell'][1]) == $ar[0] . 'N') {
                $a = $record['cell'][3];
                break;
            }
        }

        if($a === false) {
            die('test2');
            return false; // not found
        }

        $doc = new DOMDocument();
        $doc->loadHTML($a);
        $url = $doc->getElementsByTagName('a')[0]->getAttribute('href');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.hsx.vn" . $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $str = curl_exec($ch);
        curl_close($ch);


        $isin = false;

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($str);
        $xpath = new DomXPath($dom);
        $items = $xpath->query("//div[@id='symbolHistoryOverview']/table[@class='member-info']/tr");
        foreach($items as $item) {
            if(substr(trim($item->firstElementChild->nodeValue), -4) == 'ISIN') {
                $isin = trim($item->lastElementChild->nodeValue);
                break;
            }
        }

        return $isin;


        /*

        $isin = false;

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($str);
        $xpath = new DomXPath($dom);
        $items = $xpath->query("//strong[@class='bsg-fs-header__subitem']");
        foreach($items as $item) {
            if(substr($item->nodeValue, 0, 5) != 'ISIN ') {
                continue;
            }
            $isin = substr($item->nodeValue, 5);


        }

        return $isin;
        */

        return false;
    }

    // from https://static-02.vndirect.com.vn/uploads/prod/Vietnam-Stock-Snapshot_VNDIRECT_20Aug2019.xlsx
    var $map = array(
        'AAA.VN'=>'VN000000AAA4',
        'AAM.VN'=>'VN000000AAM9',
        'ABT.VN'=>'VN000000ABT2',
        'ACC.VN'=>'VN000000ACC6',
        'ACL.VN'=>'VN000000ACL7',
        'ADS.VN'=>'VN000000ADS0',
        'AGF.VN'=>'VN000000AGF0',
        'AGM.VN'=>'VN000000AGM6',
        'AGR.VN'=>'VN000000AGR5',
        'AMD.VN'=>'VN000000AMD3',
        'ANV.VN'=>'VN000000ANV3',
        'APC.VN'=>'VN000000APC8',
        'APG.VN'=>'VN000000APG9',
        'ASM.VN'=>'VN000000ASM1',
        'ASP.VN'=>'VN000000ASP4',
        'AST.VN'=>'VN000000AST6',
        'ATG.VN'=>'VN000000ATG1',
        'BBC.VN'=>'VN000000BBC6',
        'BCE.VN'=>'VN000000BCE0',
        'BCG.VN'=>'VN000000BCG5',
        'BFC.VN'=>'VN000000BFC7',
        'BHN.VN'=>'VN000000BHN0',
        'BIC.VN'=>'VN000000BIC1',
        'BID.VN'=>'VN000000BID9',
        'BMC.VN'=>'VN000000BMC3',
        'BMI.VN'=>'VN000000BMI0',
        'BMP.VN'=>'VN000000BMP5',
        'BRC.VN'=>'VN000000BRC2',
        'BSI.VN'=>'VN000000BSI7',
        'BTP.VN'=>'VN000000BTP0',
        'BTT.VN'=>'VN000000BTT2',
        'BVH.VN'=>'VN000000BVH3',
        'BWE.VN'=>'VN000000BWE8',
        'C32.VN'=>'VN000000C325',
        'C47.VN'=>'VN000000C473',
        'CAV.VN'=>'VN000000CAV6',
        'CCI.VN'=>'VN000000CCI9',
        'CCL.VN'=>'VN000000CCL3',
        'CDC.VN'=>'VN000000CDC0',
        'CEE.VN'=>'VN000000CEE4',
        'CHP.VN'=>'VN000000CHP3',
        'CIG.VN'=>'VN000000CIG0',
        'CII.VN'=>'VN000000CII6',
        'CLC.VN'=>'VN000000CLC3',
        'CLG.VN'=>'VN000000CLG4',
        'CLL.VN'=>'VN000000CLL4',
        'CLW.VN'=>'VN000000CLW1',
        'CMG.VN'=>'VN000000CMG2',
        'CMT.VN'=>'VN000000CMT5',
        'CMV.VN'=>'VN000000CMV1',
        'CMX.VN'=>'VN000000CMX7',
        'CNG.VN'=>'VN000000CNG0',
        'COM.VN'=>'VN000000COM6',
        'CRC.VN'=>'VN000000CRC0',
        'CRE.VN'=>'VN000000CRE6',
        'CSM.VN'=>'VN000000CSM7',
        'CSV.VN'=>'VN000000CSV8',
        'CTD.VN'=>'VN000000CTD4',
        'CTF.VN'=>'VN000000CTF9',
        'CTG.VN'=>'VN000000CTG7',
        'CTI.VN'=>'VN000000CTI3',
        'CTS.VN'=>'VN000000CTS2',
        'CVT.VN'=>'VN000000CVT6',
        'D2D.VN'=>'VN000000D2D0',
        'DAG.VN'=>'VN000000DAG5',
        'DAH.VN'=>'VN000000DAH3',
        'DAT.VN'=>'VN000000DAT8',
        'DBD.VN'=>'VN000000DBD0',
        'DCL.VN'=>'VN000000DCL1',
        'DCM.VN'=>'VN000000DCM9',
        'DGW.VN'=>'VN000000DGW9',
        'DHA.VN'=>'VN000000DHA3',
        'DHC.VN'=>'VN000000DHC9',
        'DHG.VN'=>'VN000000DHG0',
        'DHM.VN'=>'VN000000DHM8',
        'DIC.VN'=>'VN000000DIC7',
        'DIG.VN'=>'VN000000DIG8',
        'DLG.VN'=>'VN000000DLG2',
        'DMC.VN'=>'VN000000DMC9',
        'DPG.VN'=>'VN000000DPG3',
        'DPM.VN'=>'VN000000DPM1',
        'DPR.VN'=>'VN000000DPR0',
        'DQC.VN'=>'VN000000DQC0',
        'DRC.VN'=>'VN000000DRC8',
        'DRH.VN'=>'VN000000DRH7',
        'DRL.VN'=>'VN000000DRL9',
        'DSN.VN'=>'VN000000DSN3',
        'DTA.VN'=>'VN000000DTA8',
        'DTL.VN'=>'VN000000DTL5',
        'DTT.VN'=>'VN000000DTT8',
        'DVP.VN'=>'VN000000DVP2',
        'DXG.VN'=>'VN000000DXG7',
        'DXV.VN'=>'VN000000DXV6',
        'EIB.VN'=>'VN000000EIB7',
        'ELC.VN'=>'VN000000ELC9',
        'EMC.VN'=>'VN000000EMC7',
        'EVE.VN'=>'VN000000EVE4',
        'EVG.VN'=>'VN000000EVG9',
        'FCM.VN'=>'VN000000FCM4',
        'FCN.VN'=>'VN000000FCN2',
        'FDC.VN'=>'VN000000FDC3',
        'FIR.VN'=>'VN000000FIR0',
        'FIT.VN'=>'VN000000FIT6',
        'FLC.VN'=>'VN000000FLC6',
        'FMC.VN'=>'VN000000FMC4',
        'FPT.VN'=>'VN000000FPT1',
        'FRT.VN'=>'VN000000FRT7',
        'FTM.VN'=>'VN000000FTM8',
        'FTS.VN'=>'VN000000FTS5',
        'GAS.VN'=>'VN000000GAS3',
        'GDT.VN'=>'VN000000GDT5',
        'GEX.VN'=>'VN000000GEX5',
        'GIL.VN'=>'VN000000GIL1',
        'GMC.VN'=>'VN000000GMC2',
        'GMD.VN'=>'VN000000GMD0',
        'GSP.VN'=>'VN000000GSP1',
        'GTA.VN'=>'VN000000GTA1',
        'GTN.VN'=>'VN000000GTN4',
        'HAG.VN'=>'VN000000HAG6',
        'HAH.VN'=>'VN000000HAH4',
        'HAI.VN'=>'VN000000HAI2',
        'HAP.VN'=>'VN000000HAP7',
        'HAR.VN'=>'VN000000HAR3',
        'HAS.VN'=>'VN000000HAS1',
        'HAX.VN'=>'VN000000HAX1',
        'HBC.VN'=>'VN000000HBC3',
        'HCD.VN'=>'VN000000HCD9',
        'HCM.VN'=>'VN000000HCM0',
        'HDB.VN'=>'VN000000HDB1',
        'HDC.VN'=>'VN000000HDC9',
        'HDG.VN'=>'VN000000HDG0',
        'HHS.VN'=>'VN000000HHS6',
        'HID.VN'=>'VN000000HID6',
        'HII.VN'=>'VN000000HII5',
        'HLG.VN'=>'VN000000HLG3',
        'HMC.VN'=>'VN000000HMC0',
        'HNG.VN'=>'VN000000HNG9',
        'HOT.VN'=>'VN000000HOT0',
        'HPG.VN'=>'VN000000HPG4',
        'HPX.VN'=>'VN000000HPX9',
        'HQC.VN'=>'VN000000HQC1',
        'HRC.VN'=>'VN000000HRC9',
        'HSG.VN'=>'VN000000HSG8',
        'HSL.VN'=>'VN000000HSL8',
        'HT1.VN'=>'VN000000HT12',
        'HTI.VN'=>'VN000000HTI2',
        'HTL.VN'=>'VN000000HTL6',
        'HTT.VN'=>'VN000000HTT9',
        'HTV.VN'=>'VN000000HTV5',
        'HU1.VN'=>'VN000000HU19',
        'HU3.VN'=>'VN000000HU35',
        'HVG.VN'=>'VN000000HVG2',
        'HVX.VN'=>'VN000000HVX7',
        'IBC.VN'=>'VN000000IBC1',
        'ICF.VN'=>'VN000000ICF2',
        'IDI.VN'=>'VN000000IDI4',
        'IJC.VN'=>'VN000000IJC4',
        'IMP.VN'=>'VN000000IMP0',
        'ITA.VN'=>'VN000000ITA7',
        'ITC.VN'=>'VN000000ITC3',
        'ITD.VN'=>'VN000000ITD1',
        'JVC.VN'=>'VN000000JVC7',
        'KAC.VN'=>'VN000000KAC9',
        'KBC.VN'=>'VN000000KBC7',
        'KDC.VN'=>'VN000000KDC3',
        'KDH.VN'=>'VN000000KDH2',
        'KHP.VN'=>'VN000000KHP6',
        'KMR.VN'=>'VN000000KMR2',
        'KPF.VN'=>'VN000000KPF0',
        'KSB.VN'=>'VN000000KSB3',
        'KSH.VN'=>'VN000000KSH0',
        'L10.VN'=>'VN000000L102',
        'LAF.VN'=>'VN000000LAF0',
        'LBM.VN'=>'VN000000LBM4',
        'LCG.VN'=>'VN000000LCG4',
        'LCM.VN'=>'VN000000LCM2',
        'LDG.VN'=>'VN000000LDG2',
        'LEC.VN'=>'VN000000LEC9',
        'LGC.VN'=>'VN000000LGC4',
        'LGL.VN'=>'VN000000LGL5',
        'LHG.VN'=>'VN000000LHG3',
        'LIX.VN'=>'VN000000LIX6',
        'LM8.VN'=>'VN000000LM88',
        'LMH.VN'=>'VN000000LMH1',
        'LSS.VN'=>'VN000000LSS5',
        'MBB.VN'=>'VN000000MBB5',
        'MCG.VN'=>'VN000000MCG2',
        'MCP.VN'=>'VN000000MCP3',
        'MDG.VN'=>'VN000000MDG0',
        'MHC.VN'=>'VN000000MHC0',
        'MSN.VN'=>'VN000000MSN4',
        'MWG.VN'=>'VN000000MWG0',
        'NAF.VN'=>'VN000000NAF6',
        'NAV.VN'=>'VN000000NAV3',
        'NBB.VN'=>'VN000000NBB3',
        'NCT.VN'=>'VN000000NCT3',
        'NKG.VN'=>'VN000000NKG3',
        'NLG.VN'=>'VN000000NLG1',
        'NNC.VN'=>'VN000000NNC6',
        'NSC.VN'=>'VN000000NSC5',
        'NT2.VN'=>'VN000000NT22',
        'NTL.VN'=>'VN000000NTL4',
        'NVL.VN'=>'VN000000NVL0',
        'NVT.VN'=>'VN000000NVT3',
        'OGC.VN'=>'VN000000OGC8',
        'OPC.VN'=>'VN000000OPC9',
        'PAC.VN'=>'VN000000PAC8',
        'PAN.VN'=>'VN000000PAN5',
        'PC1.VN'=>'VN000000PC11',
        'PDN.VN'=>'VN000000PDN9',
        'PDR.VN'=>'VN000000PDR0',
        'PET.VN'=>'VN000000PET4',
        'PGC.VN'=>'VN000000PGC5',
        'PGD.VN'=>'VN000000PGD3',
        'PGI.VN'=>'VN000000PGI2',
        'PHC.VN'=>'VN000000PHC3',
        'PHR.VN'=>'VN000000PHR1',
        'PIT.VN'=>'VN000000PIT5',
        'PJT.VN'=>'VN000000PJT3',
        'PLP.VN'=>'VN000000PLP7',
        'PLX.VN'=>'VN000000PLX1',
        'PME.VN'=>'VN000000PME9',
        'PMG.VN'=>'VN000000PMG4',
        'PNC.VN'=>'VN000000PNC1',
        'PNJ.VN'=>'VN000000PNJ6',
        'POM.VN'=>'VN000000POM8',
        'PPC.VN'=>'VN000000PPC6',
        'PPI.VN'=>'VN000000PPI3',
        'PTB.VN'=>'VN000000PTB0',
        'PTC.VN'=>'VN000000PTC8',
        'PTL.VN'=>'VN000000PTL9',
        'PVD.VN'=>'VN000000PVD2',
        'PVT.VN'=>'VN000000PVT8',
        'PXI.VN'=>'VN000000PXI7',
        'PXS.VN'=>'VN000000PXS6',
        'PXT.VN'=>'VN000000PXT4',
        'QBS.VN'=>'VN000000QBS0',
        'QCG.VN'=>'VN000000QCG3',
        'RAL.VN'=>'VN000000RAL5',
        'RDP.VN'=>'VN000000RDP0',
        'REE.VN'=>'VN000000REE2',
        'RIC.VN'=>'VN000000RIC7',
        'ROS.VN'=>'VN000000ROS1',
        'S4A.VN'=>'VN000000S4A5',
        'SAB.VN'=>'VN000000SAB4',
        'SAM.VN'=>'VN000000SAM1',
        'SAV.VN'=>'VN000000SAV2',
        'SBA.VN'=>'VN000000SBA4',
        'SBT.VN'=>'VN000000SBT4',
        'SBV.VN'=>'VN000000SBV0',
        'SC5.VN'=>'VN000000SC59',
        'SCD.VN'=>'VN000000SCD6',
        'SCR.VN'=>'VN000000SCR6',
        'SCS.VN'=>'VN000000SCS4',
        'SFC.VN'=>'VN000000SFC1',
        'SFG.VN'=>'VN000000SFG2',
        'SFI.VN'=>'VN000000SFI8',
        'SGN.VN'=>'VN000000SGN6',
        'SGR.VN'=>'VN000000SGR7',
        'SGT.VN'=>'VN000000SGT3',
        'SHA.VN'=>'VN000000SHA1',
        'SHI.VN'=>'VN000000SHI4',
        'SHP.VN'=>'VN000000SHP9',
        'SII.VN'=>'VN000000SII2',
        'SJD.VN'=>'VN000000SJD1',
        'SJF.VN'=>'VN000000SJF6',
        'SJS.VN'=>'VN000000SJS9',
        'SKG.VN'=>'VN000000SKG2',
        'SMA.VN'=>'VN000000SMA1',
        'SMB.VN'=>'VN000000SMB9',
        'SMC.VN'=>'VN000000SMC7',
        'SPM.VN'=>'VN000000SPM9',
        'SRC.VN'=>'VN000000SRC6',
        'SRF.VN'=>'VN000000SRF9',
        'SSC.VN'=>'VN000000SSC4',
        'SSI.VN'=>'VN000000SSI1',
        'ST8.VN'=>'VN000000ST84',
        'STB.VN'=>'VN000000STB4',
        'STG.VN'=>'VN000000STG3',
        'STK.VN'=>'VN000000STK5',
        'SVC.VN'=>'VN000000SVC8',
        'SVI.VN'=>'VN000000SVI5',
        'SVT.VN'=>'VN000000SVT2',
        'SZL.VN'=>'VN000000SZL0',
        'TAC.VN'=>'VN000000TAC0',
        'TBC.VN'=>'VN000000TBC8',
        'TCB.VN'=>'VN000000TCB8',
        'TCD.VN'=>'VN000000TCD4',
        'TCH.VN'=>'VN000000TCH5',
        'TCL.VN'=>'VN000000TCL7',
        'TCM.VN'=>'VN000000TCM5',
        'TCO.VN'=>'VN000000TCO1',
        'TCR.VN'=>'VN000000TCR4',
        'TCT.VN'=>'VN000000TCT0',
        'TDC.VN'=>'VN000000TDC4',
        'TDG.VN'=>'VN000000TDG5',
        'TDH.VN'=>'VN000000TDH3',
        'TDM.VN'=>'VN000000TDM3',
        'TDW.VN'=>'VN000000TDW2',
        'TEG.VN'=>'VN000000TEG3',
        'TGG.VN'=>'VN000000TGG8',
        'THG.VN'=>'VN000000THG6',
        'THI.VN'=>'VN000000THI2',
        'TIE.VN'=>'VN000000TIE9',
        'TIP.VN'=>'VN000000TIP5',
        'TIX.VN'=>'VN000000TIX9',
        'TLD.VN'=>'VN000000TLD5',
        'TLG.VN'=>'VN000000TLG8',
        'TLH.VN'=>'VN000000TLH6',
        'TMP.VN'=>'VN000000TMP7',
        'TMS.VN'=>'VN000000TMS1',
        'TMT.VN'=>'VN000000TMT9',
        'TNA.VN'=>'VN000000TNA7',
        'TNC.VN'=>'VN000000TNC3',
        'TNI.VN'=>'VN000000TNI0',
        'TNT.VN'=>'VN000000TNT7',
        'TPB.VN'=>'VN000000TPB0',
        'TPC.VN'=>'VN000000TPC8',
        'TRA.VN'=>'VN000000TRA8',
        'TRC.VN'=>'VN000000TRC4',
        'TS4.VN'=>'VN000000TS43',
        'TSC.VN'=>'VN000000TSC2',
        'TTB.VN'=>'VN000000TTB2',
        'TTF.VN'=>'VN000000TTF3',
        'TVB.VN'=>'VN000000TVB8',
        'TVS.VN'=>'VN000000TVS2',
        'TVT.VN'=>'VN000000TVT0',
        'TYA.VN'=>'VN000000TYA4',
        'UDC.VN'=>'VN000000UDC2',
        'UIC.VN'=>'VN000000UIC1',
        'VAF.VN'=>'VN000000VAF9',
        'VCB.VN'=>'VN000000VCB4',
        'VCF.VN'=>'VN000000VCF5',
        'VCI.VN'=>'VN000000VCI9',
        'VDP.VN'=>'VN000000VDP2',
        'VDS.VN'=>'VN000000VDS6',
        'VFG.VN'=>'VN000000VFG6',
        'VHC.VN'=>'VN000000VHC1',
        'VHG.VN'=>'VN000000VHG2',
        'VHM.VN'=>'VN000000VHM0',
        'VIC.VN'=>'VN000000VIC9',
        'VID.VN'=>'VN000000VID7',
        'VIP.VN'=>'VN000000VIP1',
        'VIS.VN'=>'VN000000VIS5',
        'VJC.VN'=>'VN000000VJC7',
        'VMD.VN'=>'VN000000VMD9',
        'VND.VN'=>'VN000000VND7',
        'VNE.VN'=>'VN000000VNE5',
        'VNG.VN'=>'VN000000VNG0',
        'VNH.VN'=>'VN000000VNH8',
        'VNI.VN'=>'VN000000VNI6',
        'VNL.VN'=>'VN000000VNL0',
        'VNM.VN'=>'VN000000VNM8',
        'VNP.VN'=>'VN000000VNP1',
        'VNS.VN'=>'VN000000VNS5',
        'VNX.VN'=>'VN000000VNX5',
        'VNY.VN'=>'VN000000VNY3',
        'VOC.VN'=>'VN000000VOC7',
        'VOS.VN'=>'VN000000VOS3',
        'VPA.VN'=>'VN000000VPA8',
        'VPB.VN'=>'VN000000VPB6',
        'VPC.VN'=>'VN000000VPC4',
        'VPD.VN'=>'VN000000VPD2',
        'VPG.VN'=>'VN000000VPG5',
        'VPH.VN'=>'VN000000VPH3',
        'VPI.VN'=>'VN000000VPI1',
        'VPK.VN'=>'VN000000VPK7',
        'VPR.VN'=>'VN000000VPR2',
        'VPS.VN'=>'VN000000VPS0',
        'VPW.VN'=>'VN000000VPW2',
        'VQC.VN'=>'VN000000VQC2',
        'VRC.VN'=>'VN000000VRC0',
        'VRE.VN'=>'VN000000VRE6',
        'VRG.VN'=>'VN000000VRG1',
        'VSC.VN'=>'VN000000VSC8',
        'VSE.VN'=>'VN000000VSE4',
        'VSF.VN'=>'VN000000VSF1',
        'VSG.VN'=>'VN000000VSG9',
        'VSH.VN'=>'VN000000VSH7',
        'VSI.VN'=>'VN000000VSI5',
        'VSN.VN'=>'VN000000VSN5',
        'VSP.VN'=>'VN000000VSP0',
        'VST.VN'=>'VN000000VST2',
        'VT1.VN'=>'VN000000VT14',
        'VT8.VN'=>'VN000000VT89',
        'VTA.VN'=>'VN000000VTA0',
        'VTB.VN'=>'VN000000VTB8',
        'VTE.VN'=>'VN000000VTE2',
        'VTG.VN'=>'VN000000VTG7',
        'VTI.VN'=>'VN000000VTI3',
        'VTM.VN'=>'VN000000VTM5',
        'VTX.VN'=>'VN000000VTX2',
        'VVN.VN'=>'VN000000VVN9',
        'VWS.VN'=>'VN000000VWS6',
        'WSB.VN'=>'VN000000WSB8',
        'WTC.VN'=>'VN000000WTC4',
        'WTN.VN'=>'VN000000WTN1',
        'X18.VN'=>'VN000000X180',
        'X26.VN'=>'VN000000X263',
        'X77.VN'=>'VN000000X776',
        'XDH.VN'=>'VN000000XDH5',
        'XHC.VN'=>'VN000000XHC7',
        'XLV.VN'=>'VN000000XLV9',
        'XMD.VN'=>'VN000000XMD5',
        'XPH.VN'=>'VN000000XPH9',
        'YBC.VN'=>'VN000000YBC8',
        'YRC.VN'=>'VN000000YRC4',
        'YTC.VN'=>'VN000000YTC0'
    );
}