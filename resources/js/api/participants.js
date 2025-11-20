import axios from 'axios'

const prefix = '/api/v1/participants'

export function fetchParticipants(params = {}) {
  return axios.get(prefix, { params })
}

export function fetchParticipant(id) {
  return axios.get(`${prefix}/${id}`)
}

export function createParticipant(data) {
  return axios.post(prefix, data)
}

export function updateParticipant(id, data) {
  return axios.put(`${prefix}/${id}`, data)
}

export function deleteParticipant(id) {
  return axios.delete(`${prefix}/${id}`)
}
