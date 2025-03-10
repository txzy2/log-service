type SourceType = {
  name: string;
  service: string;
};

export const SOURCES: SourceType[] = [
  {name: 'Moneta', service: 'WSPG'},
  {name: 'VSK', service: 'WSPG'},
  {name: 'Internal', service: 'ADS'}
];
