import * as CryptoJS from 'crypto-js';
import {getSignType} from '../types/types';

export interface IGenerateToken {
    service: string;
    date?: string;
}

export interface IGenerateServicesToken {
    timestamp: number;
}

export const generateToken = async (data: IGenerateToken): Promise<string> => {
    const token = import.meta.env.VITE_REPORT_TOKEN;
    return CryptoJS.SHA256(token + data.service + token).toString(
        CryptoJS.enc.Hex
    );
};

export const generateServicesToken = async (timestamp: number): Promise<string> => {
    const token = import.meta.env.VITE_SERVICES_TOKEN;
    return CryptoJS.SHA256(token + timestamp + token).toString(
        CryptoJS.enc.Hex
    );
};

export const getSign = async (): Promise<getSignType> => {
    const timestamp = Math.floor(Date.now() / 1000);
    return {
        signature: await generateServicesToken(timestamp),
        timestamp
    };
};
