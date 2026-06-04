const loginUrl = 'https://agromind.uz.lazzatkafe.uz/api/auth/login';
const vehiclesUrl = 'https://agromind.uz.lazzatkafe.uz/api/vehicles';

async function run() {
  console.log('Logging in...');
  const loginRes = await fetch(loginUrl, {
    method: 'POST',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      phone: '998901111111',
      password: 'secret123',
      device_name: 'api_debug'
    })
  });

  const loginData = await loginRes.json();
  if (loginData.status !== 'success') {
    console.error('Login failed:', loginData);
    return;
  }

  const token = loginData.token;
  console.log('Login successful! Fetching vehicles...');

  const vehiclesRes = await fetch(vehiclesUrl, {
    headers: {
      'Accept': 'application/json',
      'Authorization': `Bearer ${token}`
    }
  });

  const vehiclesData = await vehiclesRes.json();
  console.log('Vehicles:', JSON.stringify(vehiclesData, null, 2));

  if (vehiclesData.vehicles && vehiclesData.vehicles.length > 0) {
    for (const v of vehiclesData.vehicles) {
      console.log(`\nFetching location for vehicle ID ${v.id} (${v.name}):`);
      const locUrl = `https://agromind.uz.lazzatkafe.uz/api/vehicles/${v.id}/location`;
      const locRes = await fetch(locUrl, {
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`
        }
      });
      const locData = await locRes.json();
      console.log(`Response for ID ${v.id}:`, JSON.stringify(locData, null, 2));
    }
  }
}

run().catch(console.error);
