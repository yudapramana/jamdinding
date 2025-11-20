import moment from 'moment';
import 'moment/dist/locale/id.js';

moment.locale('id');
export function formatDate(value) {
    if(value) {
        moment.locale('id');
        return moment(String(value)).format('YYYY-MM-DD');
    }
}

export function formatDateString(value) {
    if(value) {
        moment.locale('id');
        return moment(String(value)).format('dddd, [\r\n]YYYY-MM-DD');
    }
}

export function formatDateStringHuman(value) {
    if(value) {
        moment.locale('id');
        return moment(String(value)).format('dddd, DD MMMM YYYY');
    }
}