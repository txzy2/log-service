import * as CryptoJS from 'crypto-js';

export interface IGenerateToken {
  service: string;
  date?: string;
}

export const generateToken = async (data: IGenerateToken): Promise<string> => {
  const token = import.meta.env.VITE_REPORT_TOKEN;
  return CryptoJS.SHA256(token + data.service + token).toString(
    CryptoJS.enc.Hex
  );
};
