-- stef: 22/02/2013  remove un-used industry

SELECT CONCAT('DELETE FROM industry WHERE industrypk = ', industrypk, ';') FROM `industry`
LEFT JOIN position ON (position.industryfk = industrypk )
WHERE positionpk IS NULL AND industry.parentfk > 0
GROUP by industrypk