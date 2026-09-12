-- Sonka Bau & Sonnenimmobilien - Multi Administration – Dokumentvorlagen (Default-Templates)
-- Diese Vorlagen werden bei der Installation eingefügt.

INSERT IGNORE INTO `templates` (`type`, `name`, `description`, `content`, `is_default`) VALUES

-- Verkehrsrechtliche Anordnung
('vra', 'Antrag verkehrsrechtliche Anordnung', 'Standardvorlage für eine Verkehrsrechtliche Anordnung',
'{"sections":[
  {"id":"header","label":"Antragsteller","fields":[
    {"name":"applicant_name","label":"Name / Firma","type":"text","value":"{{company_name}}"},
    {"name":"applicant_address","label":"Anschrift","type":"textarea","value":"{{company_address}}"},
    {"name":"applicant_phone","label":"Telefon","type":"text","value":"{{company_phone}}"},
    {"name":"applicant_email","label":"E-Mail","type":"text","value":"{{company_email}}"}
  ]},
  {"id":"project","label":"Baustelle / Veranstaltung","fields":[
    {"name":"location","label":"Ort / Straße","type":"text","value":"{{location}}"},
    {"name":"start_date","label":"Beginn","type":"date","value":"{{start_date}}"},
    {"name":"end_date","label":"Ende","type":"date","value":"{{end_date}}"},
    {"name":"description","label":"Beschreibung der Maßnahme","type":"textarea","value":"{{description}}"}
  ]},
  {"id":"measures","label":"Beantragte Maßnahmen","fields":[
    {"name":"measures","label":"Angeordnete Verkehrszeichen / Verkehrseinrichtungen","type":"textarea","value":""},
    {"name":"speed_limit","label":"Geschwindigkeitsbeschränkung","type":"text","value":""},
    {"name":"road_closure","label":"Sperrungen","type":"textarea","value":""}
  ]},
  {"id":"legal","label":"Erklärung","fields":[
    {"name":"disclaimer","label":"Rechtlicher Hinweis","type":"static","value":"Die eingereichten Unterlagen wurden mit Sonka Bau & Sonnenimmobilien - Multi Administration erstellt. Sie ersetzen keine fachkundige Prüfung durch einen qualifizierten Fachmann. Alle Angaben sind nach bestem Wissen und Gewissen gemacht. Die Genehmigungsfähigkeit kann durch diese Software nicht zugesichert werden."},
    {"name":"date_place","label":"Ort, Datum","type":"text","value":"{{city}}, {{date}}"},
    {"name":"signature","label":"Unterschrift Antragsteller","type":"signature","value":""}
  ]}
]}', 1),

-- Verkehrszeichenliste
('signlist', 'Verkehrszeichenliste', 'Liste aller verwendeten Verkehrszeichen',
'{"sections":[
  {"id":"header","label":"Projektdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"project_number","label":"Projektnummer","type":"text","value":"{{project_number}}"},
    {"name":"location","label":"Baustellenort","type":"text","value":"{{location}}"},
    {"name":"period","label":"Maßnahmenzeitraum","type":"text","value":"{{start_date}} – {{end_date}}"}
  ]},
  {"id":"signs","label":"Verkehrszeichen","type":"table",
   "columns":["Pos.","Zeichen-Nr.","Bezeichnung","Anzahl","Größe","Aufstellort","Bemerkung"],
   "rows":[]}
]}', 1),

-- Materialliste
('materiallist', 'Materialliste', 'Auflistung aller benötigten Materialien',
'{"sections":[
  {"id":"header","label":"Projektdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"project_number","label":"Projektnummer","type":"text","value":"{{project_number}}"},
    {"name":"date","label":"Stand","type":"text","value":"{{date}}"}
  ]},
  {"id":"materials","label":"Materialien","type":"table",
   "columns":["Pos.","Artikel-Nr.","Bezeichnung","Menge","Einheit","Lieferant","Preis/E","Gesamt"],
   "rows":[]}
]}', 1),

-- Tagesbericht
('dailyreport', 'Tagesbericht', 'Täglich auszufüllender Bericht',
'{"sections":[
  {"id":"header","label":"Kopfdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"date","label":"Datum","type":"date","value":"{{today}}"},
    {"name":"weather","label":"Wetter","type":"text","value":""},
    {"name":"temp","label":"Temperatur","type":"text","value":""},
    {"name":"reporter","label":"Berichterstatter","type":"text","value":""}
  ]},
  {"id":"personnel","label":"Personal","type":"table",
   "columns":["Name","Funktion","Beginn","Ende","Std.","Bemerkung"],
   "rows":[]},
  {"id":"activities","label":"Tätigkeiten","fields":[
    {"name":"activities","label":"Durchgeführte Arbeiten","type":"textarea","value":""},
    {"name":"incidents","label":"Besondere Vorkommnisse","type":"textarea","value":""},
    {"name":"notes","label":"Sonstige Bemerkungen","type":"textarea","value":""}
  ]},
  {"id":"signature","label":"Unterschriften","fields":[
    {"name":"sig_bauleiter","label":"Bauleiter","type":"signature","value":""},
    {"name":"sig_auftraggeber","label":"Auftraggeber","type":"signature","value":""}
  ]}
]}', 1),

-- Baustellenkontrolle
('sitecheck', 'Baustellenkontrolle', 'Protokoll zur Baustellenüberprüfung nach RSA21',
'{"sections":[
  {"id":"header","label":"Kopfdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"location","label":"Örtlichkeit","type":"text","value":"{{location}}"},
    {"name":"check_date","label":"Kontrollzeitpunkt","type":"datetime","value":""},
    {"name":"checker","label":"Kontrolleur","type":"text","value":""}
  ]},
  {"id":"checklist","label":"Prüfliste","type":"checklist","items":[
    {"id":"c1","label":"Verkehrszeichen vollständig und lesbar aufgestellt"},
    {"id":"c2","label":"Absperrungen intakt und vollständig"},
    {"id":"c3","label":"Beleuchtung und Warneinrichtungen funktionsfähig"},
    {"id":"c4","label":"Zufahrten für Rettungsfahrzeuge freigehalten"},
    {"id":"c5","label":"Fahrbahnmarkierungen vorhanden"},
    {"id":"c6","label":"Schutzeinrichtungen für Fußgänger vorhanden"},
    {"id":"c7","label":"Sicherheitsabstände eingehalten"},
    {"id":"c8","label":"Beleuchtung der Arbeitsstelle ausreichend (Nacht)"}
  ]},
  {"id":"defects","label":"Mängel","fields":[
    {"name":"defects","label":"Festgestellte Mängel","type":"textarea","value":""},
    {"name":"measures","label":"Sofortmaßnahmen","type":"textarea","value":""},
    {"name":"followup","label":"Nachzuverfolgende Punkte","type":"textarea","value":""}
  ]},
  {"id":"signature","label":"Unterschrift","fields":[
    {"name":"signature","label":"Kontrolleur","type":"signature","value":""}
  ]}
]}', 1),

-- Abnahmeprotokoll
('acceptance', 'Abnahmeprotokoll', 'Protokoll zur Abnahme der Baustellensicherung',
'{"sections":[
  {"id":"header","label":"Projektdaten","fields":[
    {"name":"project_title","label":"Projekt","type":"text","value":"{{title}}"},
    {"name":"project_number","label":"Projektnummer","type":"text","value":"{{project_number}}"},
    {"name":"location","label":"Örtlichkeit","type":"text","value":"{{location}}"},
    {"name":"acceptance_date","label":"Abnahmedatum","type":"date","value":""},
    {"name":"contractor","label":"Auftragnehmer","type":"text","value":"{{company_name}}"}
  ]},
  {"id":"result","label":"Abnahmeergebnis","fields":[
    {"name":"result","label":"Ergebnis","type":"select","options":["Abgenommen","Abgenommen mit Auflagen","Nicht abgenommen"],"value":""},
    {"name":"conditions","label":"Auflagen / Mängel","type":"textarea","value":""},
    {"name":"deadline","label":"Behebungsfrist","type":"date","value":""}
  ]},
  {"id":"signatures","label":"Unterschriften","fields":[
    {"name":"sig_ag","label":"Auftraggeber","type":"signature","value":""},
    {"name":"sig_an","label":"Auftragnehmer","type":"signature","value":""}
  ]}
]}', 1),

-- Projektbericht
('report', 'Projektabschlussbericht', 'Zusammenfassender Bericht nach Abschluss der Maßnahme',
'{"sections":[
  {"id":"header","label":"Projektdaten","fields":[
    {"name":"project_title","label":"Projekttitel","type":"text","value":"{{title}}"},
    {"name":"project_number","label":"Projektnummer","type":"text","value":"{{project_number}}"},
    {"name":"customer","label":"Auftraggeber","type":"text","value":"{{customer}}"},
    {"name":"period","label":"Maßnahmenzeitraum","type":"text","value":"{{start_date}} – {{end_date}}"},
    {"name":"location","label":"Örtlichkeit","type":"text","value":"{{location}}"}
  ]},
  {"id":"summary","label":"Zusammenfassung","fields":[
    {"name":"summary","label":"Projektzusammenfassung","type":"textarea","value":""},
    {"name":"objectives","label":"Ziele der Maßnahme","type":"textarea","value":""},
    {"name":"results","label":"Erzielte Ergebnisse","type":"textarea","value":""},
    {"name":"problems","label":"Aufgetretene Probleme / Lösungen","type":"textarea","value":""}
  ]},
  {"id":"disclaimer","label":"Rechtlicher Hinweis","fields":[
    {"name":"disclaimer","label":"Hinweis","type":"static","value":"Diese Dokumentation wurde mit Sonka Bau & Sonnenimmobilien - Multi Administration erstellt. Die Genehmigungsfähigkeit der erstellten Unterlagen kann durch diese Software nicht zugesichert werden. Alle Planunterlagen und Dokumente sind vor der Einreichung bei der zuständigen Behörde durch einen qualifizierten Fachmann zu prüfen."}
  ]}
]}', 1);
