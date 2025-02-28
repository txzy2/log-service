import * as CryptoJS from 'crypto-js';
import {generateServicesTokenType, genTypes, getSignType, IGenerateToken} from '../types/types';

export const generateToken = async (data: IGenerateToken): Promise<string> => {
    const token: string = import.meta.env.VITE_REPORT_TOKEN;
    return CryptoJS.SHA256(token + data.service + token).toString(
        CryptoJS.enc.Hex
    );
};

const generateServicesToken = (
    {
        params: {content, method, path},
        timestamp
    }: generateServicesTokenType
): string => {
    const sign: string = import.meta.env.VITE_SERVICES_TOKEN;
    const dataToSign: string = `${method}${path}${timestamp}${typeof content === 'string' ? content : JSON.stringify(content)}`;

    return CryptoJS.HmacSHA256(dataToSign, sign).toString(CryptoJS.enc.Hex);
};

export const getSign = (params: genTypes): getSignType => {
    const timestamp: number = Math.floor(Date.now() / 1000);
    return {
        signature: generateServicesToken({timestamp, params}),
        timestamp
    };
};
